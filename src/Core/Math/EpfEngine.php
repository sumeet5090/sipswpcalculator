<?php

declare(strict_types=1);

namespace Core\Math;

/**
 * Pure, stateless financial calculation engine for Employees' Provident Fund (EPF & VPF).
 *
 * Implements authentic EPFO calculation mechanics:
 * - Monthly running-balance interest accrual at (annualRate / 12), credited annually at FY end.
 * - Statutory 12% Employee EPF + optional VPF (0%–50%).
 * - Statutory 12% Employer split: 8.33% EPS (capped at ₹1,250/mo on ₹15,000 wage ceiling) + Employer EPF share.
 * - Annual Basic + DA increment escalation.
 * - Finance Act 2021 ₹2,50,000 annual employee+VPF tax-exempt interest threshold detection.
 */
final class EpfEngine
{
    public const DEFAULT_BASIC_SALARY = 50000.0;
    public const DEFAULT_CURRENT_AGE = 28;
    public const DEFAULT_RETIREMENT_AGE = 58;
    public const DEFAULT_VPF_PERCENT = 0.0;
    public const DEFAULT_SALARY_HIKE = 7.0;
    public const DEFAULT_INTEREST_RATE = 8.25;
    public const DEFAULT_CURRENT_BALANCE = 0.0;
    public const DEFAULT_CAP_EPS = true;

    public const MIN_BASIC_SALARY = 5000.0;
    public const MAX_BASIC_SALARY = 500000.0;
    public const MIN_CURRENT_AGE = 18;
    public const MAX_CURRENT_AGE = 57;
    public const MAX_RETIREMENT_AGE = 60;
    public const MIN_VPF_PERCENT = 0.0;
    public const MAX_VPF_PERCENT = 50.0;
    public const MIN_SALARY_HIKE = 0.0;
    public const MAX_SALARY_HIKE = 20.0;
    public const MIN_INTEREST_RATE = 6.0;
    public const MAX_INTEREST_RATE = 12.0;
    public const MIN_CURRENT_BALANCE = 0.0;
    public const MAX_CURRENT_BALANCE = 50000000.0;

    private const EPS_WAGE_CEILING_CAP = 1250.0;
    private const TAX_FREE_ANNUAL_EMP_CAP = 250000.0;

    /**
     * @return array{
     *     basic_salary: float,
     *     current_age: int,
     *     retirement_age: int,
     *     tenure_years: int,
     *     vpf_percent: float,
     *     salary_hike: float,
     *     interest_rate: float,
     *     current_balance: float,
     *     cap_eps: bool,
     *     total_employee_contribution: float,
     *     total_employer_contribution: float,
     *     total_invested: float,
     *     total_interest: float,
     *     maturity_corpus: float,
     *     total_eps_diverted: float,
     *     exceeds_250k_cap: bool,
     *     schedule: list<array{
     *         year: int,
     *         age: int,
     *         opening_balance: float,
     *         employee_contribution: float,
     *         employer_contribution: float,
     *         interest_earned: float,
     *         closing_balance: float
     *     }>
     * }
     */
    public static function calculate(
        float $basicSalary,
        int $currentAge,
        int $retirementAge,
        float $vpfPercent = self::DEFAULT_VPF_PERCENT,
        float $salaryHike = self::DEFAULT_SALARY_HIKE,
        float $interestRate = self::DEFAULT_INTEREST_RATE,
        float $currentBalance = self::DEFAULT_CURRENT_BALANCE,
        bool $capEps = self::DEFAULT_CAP_EPS
    ): array {
        $basic = max(self::MIN_BASIC_SALARY, min(self::MAX_BASIC_SALARY, $basicSalary));
        $currAge = max(self::MIN_CURRENT_AGE, min(self::MAX_CURRENT_AGE, $currentAge));
        $retAge = max($currAge + 1, min(self::MAX_RETIREMENT_AGE, $retirementAge));
        $tenureYears = $retAge - $currAge;
        $vpfPct = max(self::MIN_VPF_PERCENT, min(self::MAX_VPF_PERCENT, $vpfPercent));
        $hikePct = max(self::MIN_SALARY_HIKE, min(self::MAX_SALARY_HIKE, $salaryHike));
        $ratePct = max(self::MIN_INTEREST_RATE, min(self::MAX_INTEREST_RATE, $interestRate));
        $initBalance = max(self::MIN_CURRENT_BALANCE, min(self::MAX_CURRENT_BALANCE, $currentBalance));

        $monthlyRate = ($ratePct / 100.0) / 12.0;
        $hikeDecimal = $hikePct / 100.0;

        $balance = $initBalance;
        $cumEmployee = 0.0;
        $cumEmployer = 0.0;
        $cumInterest = 0.0;
        $cumEps = 0.0;
        $exceeds250kCap = false;
        $schedule = [];

        for ($y = 1; $y <= $tenureYears; $y++) {
            $openingBalance = $balance;
            $basicThisYear = $basic * pow(1.0 + $hikeDecimal, $y - 1);

            $empMonthly = $basicThisYear * 0.12;
            $vpfMonthly = $basicThisYear * ($vpfPct / 100.0);
            $totalEmpMonthly = $empMonthly + $vpfMonthly;

            $epsMonthly = $capEps
                ? min(self::EPS_WAGE_CEILING_CAP, $basicThisYear * 0.0833)
                : ($basicThisYear * 0.0833);
            $erEpfMonthly = ($basicThisYear * 0.12) - $epsMonthly;

            $annualEmp = $totalEmpMonthly * 12.0;
            $annualEr = $erEpfMonthly * 12.0;
            $annualEps = $epsMonthly * 12.0;

            if ($annualEmp > self::TAX_FREE_ANNUAL_EMP_CAP) {
                $exceeds250kCap = true;
            }

            $runningBalance = $balance;
            $yearInterest = 0.0;

            for ($m = 1; $m <= 12; $m++) {
                $runningBalance += $totalEmpMonthly + $erEpfMonthly;
                $yearInterest += $runningBalance * $monthlyRate;
            }

            $balance = $runningBalance + $yearInterest;
            $cumEmployee += $annualEmp;
            $cumEmployer += $annualEr;
            $cumInterest += $yearInterest;
            $cumEps += $annualEps;

            $schedule[] = [
                'year' => $y,
                'age' => $currAge + $y,
                'opening_balance' => (float) round($openingBalance),
                'employee_contribution' => (float) round($annualEmp),
                'employer_contribution' => (float) round($annualEr),
                'interest_earned' => (float) round($yearInterest),
                'closing_balance' => (float) round($balance),
            ];
        }

        $totalEmpWithInit = $cumEmployee + $initBalance;
        $totalInvested = $totalEmpWithInit + $cumEmployer;

        return [
            'basic_salary' => (float) $basic,
            'current_age' => $currAge,
            'retirement_age' => $retAge,
            'tenure_years' => $tenureYears,
            'vpf_percent' => (float) $vpfPct,
            'salary_hike' => (float) $hikePct,
            'interest_rate' => (float) $ratePct,
            'current_balance' => (float) $initBalance,
            'cap_eps' => $capEps,
            'total_employee_contribution' => (float) round($totalEmpWithInit),
            'total_employer_contribution' => (float) round($cumEmployer),
            'total_invested' => (float) round($totalInvested),
            'total_interest' => (float) round($cumInterest),
            'maturity_corpus' => (float) round($balance),
            'total_eps_diverted' => (float) round($cumEps),
            'exceeds_250k_cap' => $exceeds250kCap,
            'schedule' => $schedule,
        ];
    }
}
