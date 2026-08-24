<?php

declare(strict_types=1);

namespace Core;

/**
 * InvestmentCalculator
 * Handles month-by-month compounding projections for SIP + SWP portfolios.
 */
class InvestmentCalculator
{
    /**
     * Run simulation and build yearly breakdown projections.
     *
     * @param InvestmentInputs $inputs
     * @return array List of yearly simulation rows
     */
    public function calculate(InvestmentInputs $inputs): array
    {
        $sipYears = $inputs->getYears();
        $swpYears = $inputs->getSwpYears();
        $rate = $inputs->getRate();
        $stepup = $inputs->getStepup();
        $enableSwp = $inputs->isSwpEnabled();
        $swpWithdrawal = $inputs->getSwpWithdrawal();
        $swpStepup = $inputs->getSwpStepup();
        $lumpsum = $inputs->getLumpsum();
        $swpRate = $inputs->getSwpRate();

        $swpStartYear = $sipYears + 1;
        $simulationYears = $enableSwp ? ($sipYears + $swpYears) : $sipYears;

        $netBalance = $lumpsum;
        $cumulativeInvested = $lumpsum;
        $cumulativeWithdrawals = 0.0;
        $results = [];

        if ($simulationYears <= 0) {
            return [[
                'year' => 0,
                'begin_balance' => round($lumpsum),
                'sip_monthly' => null,
                'annual_contribution' => 0.0,
                'cumulative_invested' => round($lumpsum),
                'swp_monthly' => null,
                'annual_withdrawal' => null,
                'cumulative_withdrawals' => 0.0,
                'interest' => 0.0,
                'combined_total' => round($lumpsum),
                'ltcg_tax' => 0.0,
                'post_tax_total' => round($lumpsum)
            ]];
        }

        for ($y = 1; $y <= $simulationYears; $y++) {
            $currentRate = ($y <= $sipYears) ? $rate : $swpRate;
            $monthlyRate = $currentRate / 100 / 12;

            // Determine monthly SIP for this year
            $monthlySip = ($y <= $sipYears) ? round($inputs->getSip() * pow(1 + $stepup / 100, $y - 1), 2) : 0.0;
            $annualContribution = $monthlySip * 12.0;

            // Determine monthly SWP for this year
            $monthlySwp = ($enableSwp && $y >= $swpStartYear)
                ? round($swpWithdrawal * pow(1 + $swpStepup / 100, $y - $swpStartYear), 2)
                : 0.0;

            $actualYearWithdrawn = 0.0;
            $yearBegin = $netBalance;

            // Month-by-month simulation
            for ($m = 1; $m <= 12; $m++) {
                $contrib = ($y <= $sipYears) ? $monthlySip : 0.0;
                $potentialBalance = $netBalance + $contrib;

                $withdraw = 0.0;
                if ($enableSwp && $y >= $swpStartYear && $monthlySwp > 0.0) {
                    $withdraw = ($monthlySwp > $potentialBalance) ? $potentialBalance : $monthlySwp;
                    $withdraw = max(0.0, $withdraw);
                }

                $actualYearWithdrawn += $withdraw;
                $netBalance = ($netBalance + $contrib - $withdraw) * (1 + $monthlyRate);

                // Safe guard tiny floating/rounding values
                if ($netBalance < 0.0) {
                    $netBalance = 0.0;
                }
            }

            $annualWithdrawal = $actualYearWithdrawn;
            $interestEarned = $netBalance - ($yearBegin + $annualContribution - $annualWithdrawal);

            $cumulativeInvested += $annualContribution;
            if ($enableSwp && $y >= $swpStartYear) {
                $cumulativeWithdrawals = round($cumulativeWithdrawals + $annualWithdrawal, 2);
            }

            $preTaxGains = $netBalance + $cumulativeWithdrawals - $cumulativeInvested;
            $taxableGains = max(0.0, $preTaxGains - $inputs->getLtcgExemption());
            $ltcgTax = $taxableGains * $inputs->getLtcgTaxRate();
            $postTaxCorpus = max(0.0, $netBalance - $ltcgTax);

            $results[] = [
                'year' => $y,
                'begin_balance' => round($yearBegin),
                'sip_monthly' => ($y <= $sipYears) ? $monthlySip : null,
                'annual_contribution' => $annualContribution,
                'cumulative_invested' => $cumulativeInvested,
                'swp_monthly' => ($enableSwp && $y >= $swpStartYear) ? $monthlySwp : null,
                'annual_withdrawal' => ($enableSwp && $y >= $swpStartYear) ? $annualWithdrawal : null,
                'cumulative_withdrawals' => ($enableSwp && $y >= $swpStartYear) ? $cumulativeWithdrawals : 0.0,
                'interest' => round($interestEarned),
                'combined_total' => round($netBalance),
                'ltcg_tax' => round($ltcgTax),
                'post_tax_total' => round($postTaxCorpus)
            ];
        }

        return $results;
    }

    /**
     * Calculate the cost of delaying the investment by 1 year.
     */
    public function calculateDelayCost(InvestmentInputs $inputs): float
    {
        if ($inputs->getYears() <= 1) {
            return 0.0;
        }

        $currentResults = $this->calculate($inputs);
        $delayedInputs = $inputs->withYears($inputs->getYears() - 1);
        $delayedResults = $this->calculate($delayedInputs);

        $currentFinal = (float) ($currentResults[count($currentResults) - 1]['combined_total'] ?? 0.0);
        $delayedFinal = (float) ($delayedResults[count($delayedResults) - 1]['combined_total'] ?? 0.0);

        return max(0.0, $currentFinal - $delayedFinal);
    }

    /**
     * Adjust the final corpus for inflation to show purchasing power parity.
     */
    public static function calculateInflationDiscount(float $finalCorpus, int $totalYears, float $inflationRate): float
    {
        if ($inflationRate <= 0.0 || $totalYears <= 0 || $finalCorpus <= 0.0) {
            return max(0.0, $finalCorpus);
        }
        return max(0.0, $finalCorpus / pow(1.0 + ($inflationRate / 100.0), $totalYears));
    }

    /**
     * Binary Search to find the required starting SIP to reach a target corpus.
     */
    public function calculateRequiredSip(InvestmentInputs $inputs, float $targetCorpus): float
    {
        if ($targetCorpus <= 0.0 || $inputs->getYears() <= 0) {
            return 0.0;
        }

        if ($inputs->getRate() <= 0.0 && $inputs->getStepup() <= 0.0) {
            $remaining = max(0.0, $targetCorpus - $inputs->getLumpsum());
            return round($remaining / ($inputs->getYears() * 12.0));
        }

        $zeroSipResults = $this->calculate($inputs->withSip(0.0));
        if (!empty($zeroSipResults) && $zeroSipResults[count($zeroSipResults) - 1]['combined_total'] >= $targetCorpus) {
            return 0.0;
        }

        $low = 0.0;
        $high = max($targetCorpus, ($targetCorpus / max(1, $inputs->getYears())) * 2.0, 1000000.0);
        $bestSip = 0.0;

        for ($i = 0; $i < 40; $i++) {
            $mid = ($low + $high) / 2.0;
            $testInp = $inputs->withSip($mid);
            $results = $this->calculate($testInp);
            if (empty($results)) {
                break;
            }
            $finalCorpus = (float) $results[count($results) - 1]['combined_total'];

            if (abs($finalCorpus - $targetCorpus) < 1.0) {
                $bestSip = $mid;
                break;
            } elseif ($finalCorpus < $targetCorpus) {
                $low = $mid;
            } else {
                $high = $mid;
            }
            $bestSip = $mid;
        }

        return round($bestSip);
    }

    /**
     * Binary Search to find the required initial corpus to sustain a specific SWP plan.
     */
    public function calculateRequiredStartingCorpusForSwp(InvestmentInputs $inputs): float
    {
        if (!$inputs->isSwpEnabled() || $inputs->getSwpWithdrawal() <= 0.0 || $inputs->getSwpYears() <= 0) {
            return 0.0;
        }

        if ($inputs->getSwpRate() <= 0.0 && $inputs->getSwpStepup() <= 0.0) {
            return round($inputs->getSwpWithdrawal() * 12.0 * $inputs->getSwpYears());
        }

        $totalEscalatedWithdrawals = 0.0;
        for ($k = 0; $k < $inputs->getSwpYears(); $k++) {
            $totalEscalatedWithdrawals += 12.0 * $inputs->getSwpWithdrawal() * pow(1.0 + $inputs->getSwpStepup() / 100.0, $k);
        }

        $low = 0.0;
        $high = max($totalEscalatedWithdrawals * 1.5, $inputs->getSwpWithdrawal() * 12.0 * $inputs->getSwpYears() * 5.0, 1000000.0);
        $bestCorpus = $high;

        for ($i = 0; $i < 40; $i++) {
            $mid = ($low + $high) / 2.0;
            $testInp = InvestmentInputs::fromValues(
                0.0,
                max(0, $inputs->getYears()),
                $inputs->getRate(),
                $inputs->getStepup(),
                true,
                $inputs->getSwpWithdrawal(),
                $inputs->getSwpStepup(),
                $inputs->getSwpYears(),
                $mid,
                $inputs->getSwpRate(),
                $inputs->getInflation(),
                $inputs->getLtcgExemption(),
                $inputs->getLtcgTaxRate()
            );

            $results = $this->calculate($testInp);
            if (empty($results)) {
                break;
            }
            $finalBalance = (float) $results[count($results) - 1]['combined_total'];

            $ranOutEarly = false;
            $resCount = count($results);
            for ($rIdx = 0; $rIdx < $resCount - 1; $rIdx++) {
                if ($results[$rIdx]['combined_total'] <= 0) {
                    $ranOutEarly = true;
                    break;
                }
            }

            if (!$ranOutEarly && $finalBalance >= 0.0) {
                $bestCorpus = $mid;
                if (abs($finalBalance) < 1.0) {
                    break;
                }
                $high = $mid;
            } else {
                $low = $mid;
            }
        }

        return round($bestCorpus);
    }

    /**
     * Calculate potential Section 112A Tax-Harvesting alpha savings.
     * Systematically realizing up to ₹1,25,000 of LTCG per year tax-free resets the cost basis.
     *
     * @param InvestmentInputs $inputs
     * @param array<int, array<string, mixed>> $results
     * @return array{standardTax: float, harvestedTax: float, cumulativeSavings: float, totalHarvestedGains: float}
     */
    public function calculateTaxHarvestingSavings(InvestmentInputs $inputs, array $results): array
    {
        if (empty($results)) {
            return [
                'standardTax' => 0.0,
                'harvestedTax' => 0.0,
                'cumulativeSavings' => 0.0,
                'totalHarvestedGains' => 0.0
            ];
        }

        $lastRow = $results[count($results) - 1];
        $standardTax = (float) ($lastRow['ltcg_tax'] ?? 0);
        $exemptionPerYear = $inputs->getLtcgExemption();
        $taxRate = $inputs->getLtcgTaxRate();

        $totalYears = count($results);
        $maxHarvestable = $totalYears * $exemptionPerYear;
        $totalGains = max(0.0, ((float) $lastRow['combined_total'] + (float) ($lastRow['cumulative_withdrawals'] ?? 0)) - (float) $lastRow['cumulative_invested']);
        $actualHarvestedGains = min($totalGains, $maxHarvestable);

        $remainingTaxable = max(0.0, $totalGains - $actualHarvestedGains);
        $harvestedTax = round($remainingTaxable * $taxRate);
        $cumulativeSavings = max(0.0, $standardTax - $harvestedTax);

        return [
            'standardTax' => $standardTax,
            'harvestedTax' => $harvestedTax,
            'cumulativeSavings' => $cumulativeSavings,
            'totalHarvestedGains' => $actualHarvestedGains,
        ];
    }
}
