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
                'sip_monthly' => 0.0,
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
                $cumulativeWithdrawals += $annualWithdrawal;
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
}
