<?php

declare(strict_types=1);

namespace Services;

use Core\CurrencyHelper;
use Core\Inputs\CagrInputs;
use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\CompoundInterestInputs;
use Core\Inputs\EmiInputs;
use Core\Inputs\EpfInputs;
use Core\Inputs\FdInputs;
use Core\Inputs\GratuityInputs;
use Core\Inputs\IncomeTaxInputs;
use Core\Inputs\InflationInputs;
use Core\Inputs\NpsInputs;
use Core\Inputs\PostRetirementInputs;
use Core\Inputs\PpfInputs;
use Core\Inputs\TaxHarvestInputs;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Core\Math\CagrEngine;
use Core\Math\CompoundInterestEngine;
use Core\Math\EmiEngine;
use Core\Math\EpfEngine;
use Core\Math\FdEngine;
use Core\Math\GratuityEngine;
use Core\Math\IncomeTaxEngine;
use Core\Math\InflationEngine;
use Core\Math\NpsEngine;
use Core\Math\PostRetirementEngine;
use Core\Math\PpfEngine;
use Core\Math\TaxHarvestEngine;
use Core\PurchasingPowerHelper;

/**
 * Computes SSR initial KPI cards, milestone breakdown schedules, and telemetry strip data
 * across both core SIP/SWP strategies and all 12 specialized calculator engines.
 */
class GuideSsrHydrationBuilder
{
    private InvestmentCalculator $calculator;

    public function __construct(?InvestmentCalculator $calculator = null)
    {
        $this->calculator = $calculator ?? new InvestmentCalculator();
    }

    /**
     * @return array{
     *     initial_kpis: array<string, mixed>,
     *     initial_schedule: list<array<string, mixed>>,
     *     initial_telemetry: array<string, mixed>
     * }
     */
    public function build(
        CalculatorInputsInterface $inputs,
        string $calculatorType,
        ?float $targetCalculatedSip
    ): array {
        if ($inputs instanceof InvestmentInputs) {
            return $this->buildCoreHydration($inputs, $calculatorType, $targetCalculatedSip);
        }

        return $this->buildSpecializedHydration($inputs);
    }

    /**
     * @return array{
     *     initial_kpis: array<string, mixed>,
     *     initial_schedule: list<array<string, mixed>>,
     *     initial_telemetry: array<string, mixed>
     * }
     */
    private function buildCoreHydration(
        InvestmentInputs $inputs,
        string $calculatorType,
        ?float $targetCalculatedSip
    ): array {
        $simInputs = ($targetCalculatedSip !== null && $calculatorType === 'target_corpus')
            ? $inputs->withSip(round($targetCalculatedSip))
            : $inputs;

        $results = $this->calculator->calculate($simInputs);
        if (empty($results)) {
            return ['initial_kpis' => [], 'initial_schedule' => [], 'initial_telemetry' => []];
        }

        $sipYears = $simInputs->getYears();
        $swpYears = $simInputs->getSwpYears();
        $totalYears = $simInputs->isSwpEnabled() ? ($sipYears + $swpYears) : $sipYears;
        $isDualMode = ($simInputs->isSwpEnabled() && $sipYears > 0);

        $lastRow = end($results);
        $totalInvested = (float) $lastRow['cumulative_invested'];
        $finalCorpus = (float) $lastRow['combined_total'];
        $totalWithdrawn = (float) ($lastRow['cumulative_withdrawals'] ?? 0.0);
        $finalGains = ($finalCorpus + $totalWithdrawn) - $totalInvested;

        $maturedRow = ($isDualMode && isset($results[$sipYears - 1]))
            ? $results[$sipYears - 1]
            : $lastRow;
        $maturedCorpus = (float) $maturedRow['combined_total'];
        $maturedInvested = (float) $maturedRow['cumulative_invested'];
        $maturedGains = max(0.0, $maturedCorpus - $maturedInvested);

        $card2Value = $isDualMode ? $maturedCorpus : $finalGains;
        $gainPct = $isDualMode
            ? ($maturedInvested > 0 ? (int) round(($maturedGains / $maturedInvested) * 100) : 0)
            : ($totalInvested > 0 ? (int) round(($finalGains / $totalInvested) * 100) : 0);

        $initialKpis = [
            'is_dual_mode'           => $isDualMode,
            'sip_years'              => $sipYears,
            'swp_years'              => $swpYears,
            'total_years'            => $totalYears,
            'invested_compact'       => CurrencyHelper::formatCompactInr($totalInvested),
            'invested_exact'         => CurrencyHelper::formatInr($totalInvested),
            'maturity_compact'       => CurrencyHelper::formatCompactInr($card2Value),
            'maturity_exact'         => CurrencyHelper::formatInr($card2Value),
            'maturity_gains_compact' => CurrencyHelper::formatCompactInr($maturedGains),
            'maturity_gain_pct'      => $gainPct,
            'withdrawn_compact'      => CurrencyHelper::formatCompactInr($totalWithdrawn),
            'withdrawn_exact'        => CurrencyHelper::formatInr($totalWithdrawn),
            'corpus_compact'         => CurrencyHelper::formatCompactInr($finalCorpus),
            'corpus_exact'           => CurrencyHelper::formatInr($finalCorpus),
            'lifetime_gains_compact' => CurrencyHelper::formatCompactInr($finalGains),
        ];

        return [
            'initial_kpis'      => $initialKpis,
            'initial_schedule'  => $this->extractMilestoneRows($results),
            'initial_telemetry' => PurchasingPowerHelper::buildTelemetry($finalCorpus, $simInputs),
        ];
    }

    /**
     * @return array{
     *     initial_kpis: array<string, mixed>,
     *     initial_schedule: list<array<string, mixed>>,
     *     initial_telemetry: array<string, mixed>
     * }
     */
    private function buildSpecializedHydration(CalculatorInputsInterface $inputs): array
    {
        return match (true) {
            $inputs instanceof CompoundInterestInputs => $this->hydrateCompoundInterest($inputs),
            $inputs instanceof CagrInputs             => $this->hydrateCagr($inputs),
            $inputs instanceof EmiInputs              => $this->hydrateEmi($inputs),
            $inputs instanceof InflationInputs        => $this->hydrateInflation($inputs),
            $inputs instanceof PpfInputs              => $this->hydratePpf($inputs),
            $inputs instanceof FdInputs               => $this->hydrateFd($inputs),
            $inputs instanceof GratuityInputs         => $this->hydrateGratuity($inputs),
            $inputs instanceof NpsInputs              => $this->hydrateNps($inputs),
            $inputs instanceof PostRetirementInputs   => $this->hydratePostRetirement($inputs),
            $inputs instanceof EpfInputs              => $this->hydrateEpf($inputs),
            $inputs instanceof TaxHarvestInputs       => $this->hydrateTaxHarvest($inputs),
            $inputs instanceof IncomeTaxInputs        => $this->hydrateIncomeTax($inputs),
            default => ['initial_kpis' => [], 'initial_schedule' => [], 'initial_telemetry' => []],
        };
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateCompoundInterest(CompoundInterestInputs $inputs): array
    {
        $res = (new CompoundInterestEngine())->calculate(
            $inputs->getPrincipal(),
            $inputs->getRate(),
            $inputs->getYears(),
            $inputs->getFrequency()
        );
        $principal = (float) $res['principal'];
        $interest = (float) $res['total_interest'];
        $finalAmount = (float) $res['final_amount'];
        $gainPct = $principal > 0 ? (int) round(($interest / $principal) * 100) : 0;

        $rows = [];
        foreach ($res['schedule'] as $item) {
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => $principal,
                'interest'               => (float) $item['interest_earned'],
                'combined_total'         => (float) $item['closing_balance'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'     => false,
                'invested_compact' => CurrencyHelper::formatCompactInr($principal),
                'invested_exact'   => CurrencyHelper::formatInr($principal),
                'maturity_compact' => CurrencyHelper::formatCompactInr($interest),
                'maturity_exact'   => CurrencyHelper::formatInr($interest),
                'gain_badge_text'  => "+{$gainPct}% (EAR: {$res['effective_annual_rate']}%)",
                'corpus_compact'   => CurrencyHelper::formatCompactInr($finalAmount),
                'corpus_exact'     => CurrencyHelper::formatInr($finalAmount),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateCagr(CagrInputs $inputs): array
    {
        $initial = $inputs->getInitialValue();
        $final = $inputs->getFinalValue();
        $years = $inputs->getYears();
        $res = (new CagrEngine())->calculate($initial, $final, $years);

        $fullYears = max(1, (int) ceil($years));
        $annualRate = $res['cagr_percentage'] / 100.0;
        $prevVal = $initial;
        $rows = [];
        for ($yr = 1; $yr <= $fullYears; $yr++) {
            $tFrac = min((float) $yr, $years);
            $currentVal = round($initial * pow(1.0 + $annualRate, $tFrac));
            $rows[] = [
                'year'                   => $yr,
                'cumulative_invested'    => $initial,
                'interest'               => $currentVal - $prevVal,
                'combined_total'         => $currentVal,
                'cumulative_withdrawals' => 0.0,
            ];
            $prevVal = $currentVal;
        }

        $pct = (float) $res['absolute_return_percentage'];
        $sign = $pct >= 0 ? '+' : '';
        $cagrFormatted = sprintf('%.2f%% p.a.', $res['cagr_percentage']);

        return [
            'initial_kpis' => [
                'is_dual_mode'     => false,
                'invested_compact' => CurrencyHelper::formatCompactInr($initial),
                'invested_exact'   => CurrencyHelper::formatInr($initial),
                'maturity_compact' => CurrencyHelper::formatCompactInr($res['total_gain']),
                'maturity_exact'   => CurrencyHelper::formatInr($res['total_gain']),
                'gain_badge_text'  => sprintf('%s%.1f%% (%.2fx)', $sign, $pct, $res['multiplier']),
                'corpus_compact'   => $cagrFormatted,
                'corpus_exact'     => $cagrFormatted,
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateEmi(EmiInputs $inputs): array
    {
        $res = (new EmiEngine())->calculate($inputs->getPrincipal(), $inputs->getRate(), $inputs->getYears());
        $principal = (float) $res['principal'];
        $interest = (float) $res['total_interest'];
        $totalPayment = $principal + $interest;
        $emiRounded = round((float) $res['monthly_emi']);

        $cumulativePrincipalPaid = 0.0;
        $rows = [];
        foreach ($res['schedule'] as $item) {
            $cumulativePrincipalPaid += (float) $item['principal_paid'];
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => round($cumulativePrincipalPaid),
                'interest'               => (float) $item['interest_paid'],
                'combined_total'         => (float) $item['closing_balance'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'      => false,
                'invested_compact'  => CurrencyHelper::formatCompactInr($principal),
                'invested_exact'    => CurrencyHelper::formatInr($principal),
                'maturity_compact'  => CurrencyHelper::formatCompactInr($interest),
                'maturity_exact'    => CurrencyHelper::formatInr($interest),
                'gain_badge_text'   => sprintf('%.1f%% of Loan', $res['interest_ratio_percentage']),
                'withdrawn_compact' => CurrencyHelper::formatCompactInr($totalPayment),
                'withdrawn_exact'   => CurrencyHelper::formatInr($totalPayment),
                'corpus_compact'    => CurrencyHelper::formatCompactInr($emiRounded) . ' / mo',
                'corpus_exact'      => CurrencyHelper::formatInr($emiRounded) . ' / mo',
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateInflation(InflationInputs $inputs): array
    {
        $pv = $inputs->getAmount();
        $res = (new InflationEngine())->calculate($pv, $inputs->getRate(), $inputs->getYears());

        $prevCost = $pv;
        $rows = [];
        foreach ($res['schedule'] as $item) {
            $step = (float) $item['future_cost'] - $prevCost;
            $prevCost = (float) $item['future_cost'];
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => $pv,
                'interest'               => $step,
                'combined_total'         => (float) $item['future_cost'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'     => false,
                'invested_compact' => CurrencyHelper::formatCompactInr($pv),
                'invested_exact'   => CurrencyHelper::formatInr($pv),
                'maturity_compact' => CurrencyHelper::formatCompactInr($res['cost_increase']),
                'maturity_exact'   => CurrencyHelper::formatInr($res['cost_increase']),
                'gain_badge_text'  => sprintf('-%.1f%% Value', $res['purchasing_power_loss_percentage']),
                'corpus_compact'   => CurrencyHelper::formatCompactInr($res['future_cost']),
                'corpus_exact'     => CurrencyHelper::formatInr($res['future_cost']),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydratePpf(PpfInputs $inputs): array
    {
        $res = PpfEngine::calculate(
            $inputs->getAnnualInvestment(),
            $inputs->getRate(),
            $inputs->getYears(),
            $inputs->getTiming()
        );

        $cumDeposit = 0.0;
        $rows = [];
        foreach ($res['schedule'] as $item) {
            $cumDeposit += (float) $item['annual_deposit'];
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => $cumDeposit,
                'interest'               => (float) $item['interest_earned'],
                'combined_total'         => (float) $item['closing_balance'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'     => false,
                'invested_compact' => CurrencyHelper::formatCompactInr($res['total_invested']),
                'invested_exact'   => CurrencyHelper::formatInr($res['total_invested']),
                'maturity_compact' => CurrencyHelper::formatCompactInr($res['total_interest']),
                'maturity_exact'   => CurrencyHelper::formatInr($res['total_interest']),
                'gain_badge_text'  => '100% Tax-Free (EEE)',
                'corpus_compact'   => CurrencyHelper::formatCompactInr($res['maturity_amount']),
                'corpus_exact'     => CurrencyHelper::formatInr($res['maturity_amount']),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateFd(FdInputs $inputs): array
    {
        $res = FdEngine::calculate(
            $inputs->getAmount(),
            $inputs->getRate(),
            $inputs->getYears(),
            $inputs->isSenior(),
            $inputs->getPayoutFrequency()
        );

        $cumPayout = 0.0;
        $rows = [];
        foreach ($res['yearly_schedule'] as $item) {
            $cumPayout += (float) $item['payout_withdrawn'];
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => (float) $res['principal'],
                'interest'               => (float) $item['interest_earned'],
                'combined_total'         => (float) $item['closing_balance'],
                'cumulative_withdrawals' => round($cumPayout),
            ];
        }

        $finalVal = $inputs->getPayoutFrequency() === 'cumulative'
            ? (float) $res['maturity_amount']
            : (float) $res['periodic_payout'];

        return [
            'initial_kpis' => [
                'is_dual_mode'     => false,
                'invested_compact' => CurrencyHelper::formatCompactInr($res['principal']),
                'invested_exact'   => CurrencyHelper::formatInr($res['principal']),
                'maturity_compact' => CurrencyHelper::formatCompactInr($res['total_interest']),
                'maturity_exact'   => CurrencyHelper::formatInr($res['total_interest']),
                'gain_badge_text'  => $inputs->isSenior() ? '+0.5% Senior Card' : "{$res['effective_rate']}% Card Rate",
                'corpus_compact'   => CurrencyHelper::formatCompactInr($finalVal),
                'corpus_exact'     => CurrencyHelper::formatInr($finalVal),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateGratuity(GratuityInputs $inputs): array
    {
        $res = GratuityEngine::calculate(
            $inputs->getMonthlySalary(),
            $inputs->getTenureYears(),
            $inputs->getTenureMonths(),
            $inputs->isCoveredByAct()
        );

        $rows = [];
        foreach ($res['schedule'] as $item) {
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => (float) $res['monthly_salary'],
                'interest'               => (float) $item['accrued_amount'],
                'combined_total'         => (float) $item['accrued_amount'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'      => false,
                'invested_compact'  => CurrencyHelper::formatCompactInr($res['monthly_salary']),
                'invested_exact'    => CurrencyHelper::formatInr($res['monthly_salary']),
                'maturity_compact'  => CurrencyHelper::formatCompactInr($res['tax_exempt_amount']),
                'maturity_exact'    => CurrencyHelper::formatInr($res['tax_exempt_amount']),
                'gain_badge_text'   => $res['is_eligible'] ? '✓ Statutory Vested' : '⚠️ Not Vested (<5 Yrs)',
                'withdrawn_compact' => CurrencyHelper::formatCompactInr($res['taxable_amount']),
                'withdrawn_exact'   => CurrencyHelper::formatInr($res['taxable_amount']),
                'corpus_compact'    => CurrencyHelper::formatCompactInr($res['gratuity_amount']),
                'corpus_exact'      => CurrencyHelper::formatInr($res['gratuity_amount']),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateNps(NpsInputs $inputs): array
    {
        $res = NpsEngine::calculate(
            $inputs->getMonthlyContribution(),
            $inputs->getCurrentAge(),
            $inputs->getRetirementAge(),
            $inputs->getExpectedReturnRate(),
            $inputs->getAnnuityPercent(),
            $inputs->getAnnuityReturnRate()
        );

        $rows = [];
        foreach ($res['schedule'] as $item) {
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => (float) $item['invested'],
                'interest'               => (float) $item['interest'],
                'combined_total'         => (float) $item['balance'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'      => false,
                'invested_compact'  => CurrencyHelper::formatCompactInr($res['total_invested']),
                'invested_exact'    => CurrencyHelper::formatInr($res['total_invested']),
                'maturity_compact'  => CurrencyHelper::formatCompactInr($res['total_interest']),
                'maturity_exact'    => CurrencyHelper::formatInr($res['total_interest']),
                'gain_badge_text'   => $res['is_small_corpus_eligible']
                    ? '100% Lump Sum Eligible (≤₹5L)'
                    : '✓ 60% Tax-Free Lump Sum',
                'withdrawn_compact' => CurrencyHelper::formatCompactInr($res['lump_sum_amount']),
                'withdrawn_exact'   => CurrencyHelper::formatInr($res['lump_sum_amount']),
                'corpus_compact'    => CurrencyHelper::formatCompactInr($res['monthly_pension']),
                'corpus_exact'      => CurrencyHelper::formatInr($res['monthly_pension']),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydratePostRetirement(PostRetirementInputs $inputs): array
    {
        $pension = $inputs->getGuaranteedPension();
        $res = PostRetirementEngine::calculate(
            $inputs->getExistingCorpus(),
            $inputs->getGratuityLumpSum(),
            $inputs->getNpsLumpSum(),
            $pension,
            $inputs->getMonthlyExpense(),
            $inputs->getExpectedReturn(),
            $inputs->getInflationRate(),
            $inputs->getCurrentAge(),
            $inputs->getPlanningHorizonYears()
        );

        $rows = [];
        $cumSwp = 0.0;
        foreach ($res['schedule'] as $item) {
            $cumSwp += (float) $item['annual_swp_drawn'];
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => (float) $res['total_initial_corpus'],
                'interest'               => (float) $item['growth_earned'],
                'combined_total'         => (float) $item['closing_balance'],
                'cumulative_withdrawals' => round($cumSwp),
            ];
        }

        $depletionAge = $res['depletion_age'] ?? 0;
        $depletionYear = $res['depletion_year'] ?? 0;

        return [
            'initial_kpis' => [
                'is_dual_mode'      => false,
                'invested_compact'  => CurrencyHelper::formatCompactInr($res['total_initial_corpus']),
                'invested_exact'    => CurrencyHelper::formatInr($res['total_initial_corpus']),
                'maturity_compact'  => CurrencyHelper::formatCompactInr($pension),
                'maturity_exact'    => CurrencyHelper::formatInr($pension),
                'gain_badge_text'   => $res['is_perpetual']
                    ? '✓ 100% Fully Funded (Age 90+)'
                    : "⚠️ Depleted at Age {$depletionAge} (Yr {$depletionYear})",
                'withdrawn_compact' => CurrencyHelper::formatCompactInr($res['initial_monthly_swp']),
                'withdrawn_exact'   => CurrencyHelper::formatInr($res['initial_monthly_swp']),
                'corpus_compact'    => $res['is_perpetual'] ? 'Age 90+' : "Age {$depletionAge}",
                'corpus_exact'      => $res['is_perpetual'] ? 'Perpetual Lifespan' : "Depleted in Year {$depletionYear}",
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateEpf(EpfInputs $inputs): array
    {
        $res = EpfEngine::calculate(
            $inputs->getBasicSalary(),
            $inputs->getCurrentAge(),
            $inputs->getRetirementAge(),
            $inputs->getVpfPercent(),
            $inputs->getSalaryHike(),
            $inputs->getInterestRate(),
            $inputs->getCurrentBalance(),
            $inputs->isCapEps()
        );

        $cumInvested = $inputs->getCurrentBalance();
        $rows = [];
        foreach ($res['schedule'] as $item) {
            $cumInvested += (float) $item['employee_contribution'] + (float) $item['employer_contribution'];
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => $cumInvested,
                'interest'               => (float) $item['interest_earned'],
                'combined_total'         => (float) $item['closing_balance'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'      => false,
                'invested_compact'  => CurrencyHelper::formatCompactInr($res['total_employee_contribution']),
                'invested_exact'    => CurrencyHelper::formatInr($res['total_employee_contribution']),
                'maturity_compact'  => CurrencyHelper::formatCompactInr($res['total_employer_contribution']),
                'maturity_exact'    => CurrencyHelper::formatInr($res['total_employer_contribution']),
                'gain_badge_text'   => $res['exceeds_250k_cap']
                    ? '⚠️ Exceeds ₹2.5L Tax-Free Cap'
                    : '✓ 100% Tax-Free (EEE)',
                'withdrawn_compact' => CurrencyHelper::formatCompactInr($res['total_interest']),
                'withdrawn_exact'   => CurrencyHelper::formatInr($res['total_interest']),
                'corpus_compact'    => CurrencyHelper::formatCompactInr($res['maturity_corpus']),
                'corpus_exact'      => CurrencyHelper::formatInr($res['maturity_corpus']),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateTaxHarvest(TaxHarvestInputs $inputs): array
    {
        $res = TaxHarvestEngine::calculate(
            $inputs->getPortfolioValue(),
            $inputs->getInvestedCost(),
            $inputs->getExpectedReturn(),
            $inputs->getHoldingYears(),
            $inputs->getGrandfatherFmv(),
            $inputs->isIncludeCess()
        );

        $rows = [];
        foreach ($res['schedule'] as $item) {
            $rows[] = [
                'year'                   => (int) $item['year'],
                'cumulative_invested'    => (float) $res['effective_cost_basis'],
                'interest'               => (float) $item['tax_saved'],
                'combined_total'         => (float) $item['net_harvested_wealth'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'      => false,
                'invested_compact'  => CurrencyHelper::formatCompactInr($res['buy_hold_tax']),
                'invested_exact'    => CurrencyHelper::formatInr($res['buy_hold_tax']),
                'maturity_compact'  => CurrencyHelper::formatCompactInr($res['harvested_tax']),
                'maturity_exact'    => CurrencyHelper::formatInr($res['harvested_tax']),
                'gain_badge_text'   => "{$res['effective_tax_rate_pct']}% Effective LTCG",
                'withdrawn_compact' => CurrencyHelper::formatCompactInr($res['cumulative_tax_saved']),
                'withdrawn_exact'   => CurrencyHelper::formatInr($res['cumulative_tax_saved']),
                'corpus_compact'    => CurrencyHelper::formatCompactInr($res['net_post_tax_wealth']),
                'corpus_exact'      => CurrencyHelper::formatInr($res['net_post_tax_wealth']),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * @return array{initial_kpis: array<string, mixed>, initial_schedule: list<array<string, mixed>>, initial_telemetry: array<string, mixed>}
     */
    private function hydrateIncomeTax(IncomeTaxInputs $inputs): array
    {
        $res = IncomeTaxEngine::calculate(
            $inputs->getGrossSalary(),
            $inputs->getBasicSalary(),
            $inputs->getHraReceived(),
            $inputs->getRentPaid(),
            $inputs->isMetro(),
            $inputs->getSec80c(),
            $inputs->getSec80d(),
            $inputs->getNps80ccd1b(),
            $inputs->getHomeLoan24b()
        );

        $rows = [];
        foreach ($res['schedule'] as $item) {
            $rows[] = [
                'year'                   => (int) $item['bracket_index'],
                'cumulative_invested'    => (float) $item['old_tax'],
                'interest'               => (float) $item['savings'],
                'combined_total'         => (float) $item['new_tax'],
                'cumulative_withdrawals' => 0.0,
            ];
        }

        return [
            'initial_kpis' => [
                'is_dual_mode'      => false,
                'invested_compact'  => CurrencyHelper::formatCompactInr($res['hra_exemption']),
                'invested_exact'    => CurrencyHelper::formatInr($res['hra_exemption']),
                'maturity_compact'  => CurrencyHelper::formatCompactInr($res['old_total_tax']),
                'maturity_exact'    => CurrencyHelper::formatInr($res['old_total_tax']),
                'gain_badge_text'   => $res['recommended_regime'] === 'new' ? 'Winner: New Regime' : 'Winner: Old Regime',
                'withdrawn_compact' => CurrencyHelper::formatCompactInr($res['new_total_tax']),
                'withdrawn_exact'   => CurrencyHelper::formatInr($res['new_total_tax']),
                'corpus_compact'    => CurrencyHelper::formatCompactInr($res['tax_savings']),
                'corpus_exact'      => CurrencyHelper::formatInr($res['tax_savings']),
            ],
            'initial_schedule'  => $this->extractMilestoneRows($rows),
            'initial_telemetry' => [],
        ];
    }

    /**
     * Select milestone years (Year 1, every 5th year, and final year) formatted for mobile SSR cards/list.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function extractMilestoneRows(array $rows): array
    {
        $totalCount = count($rows);
        if ($totalCount === 0) {
            return [];
        }

        $milestones = [];
        foreach ($rows as $row) {
            $y = (int) $row['year'];
            if ($y === 1 || $y % 5 === 0 || $y === $totalCount) {
                $withdrawals = (float) ($row['cumulative_withdrawals'] ?? 0.0);
                $milestones[] = [
                    'year'                   => $y,
                    'is_final'               => $y === $totalCount,
                    'cumulative_invested'    => CurrencyHelper::formatInr((float) $row['cumulative_invested']),
                    'combined_total'         => CurrencyHelper::formatInr((float) $row['combined_total']),
                    'interest'               => CurrencyHelper::formatInr((float) $row['interest']),
                    'cumulative_withdrawals' => CurrencyHelper::formatInr($withdrawals),
                    'has_withdrawals'        => $withdrawals > 0.0,
                ];
            }
        }

        return $milestones;
    }
}
