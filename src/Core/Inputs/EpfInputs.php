<?php

declare(strict_types=1);

namespace Core\Inputs;

use Core\Math\EpfEngine;

/**
 * Immutable Typed Input Value Object for EPF & VPF Calculator.
 */
final class EpfInputs implements CalculatorInputsInterface
{
    private float $basicSalary;
    private int $currentAge;
    private int $retirementAge;
    private float $vpfPercent;
    private float $salaryHike;
    private float $interestRate;
    private float $currentBalance;
    private bool $capEps;

    public function __construct(
        float $basicSalary,
        int $currentAge,
        int $retirementAge,
        float $vpfPercent = EpfEngine::DEFAULT_VPF_PERCENT,
        float $salaryHike = EpfEngine::DEFAULT_SALARY_HIKE,
        float $interestRate = EpfEngine::DEFAULT_INTEREST_RATE,
        float $currentBalance = EpfEngine::DEFAULT_CURRENT_BALANCE,
        bool $capEps = EpfEngine::DEFAULT_CAP_EPS
    ) {
        $this->basicSalary = max(EpfEngine::MIN_BASIC_SALARY, min(EpfEngine::MAX_BASIC_SALARY, $basicSalary));
        $this->currentAge = max(EpfEngine::MIN_CURRENT_AGE, min(EpfEngine::MAX_CURRENT_AGE, $currentAge));
        $this->retirementAge = max($this->currentAge + 1, min(EpfEngine::MAX_RETIREMENT_AGE, $retirementAge));
        $this->vpfPercent = max(EpfEngine::MIN_VPF_PERCENT, min(EpfEngine::MAX_VPF_PERCENT, $vpfPercent));
        $this->salaryHike = max(EpfEngine::MIN_SALARY_HIKE, min(EpfEngine::MAX_SALARY_HIKE, $salaryHike));
        $this->interestRate = max(EpfEngine::MIN_INTEREST_RATE, min(EpfEngine::MAX_INTEREST_RATE, $interestRate));
        $this->currentBalance = max(EpfEngine::MIN_CURRENT_BALANCE, min(EpfEngine::MAX_CURRENT_BALANCE, $currentBalance));
        $this->capEps = $capEps;
    }

    public function getBasicSalary(): float
    {
        return $this->basicSalary;
    }

    public function getCurrentAge(): int
    {
        return $this->currentAge;
    }

    public function getRetirementAge(): int
    {
        return $this->retirementAge;
    }

    public function getVpfPercent(): float
    {
        return $this->vpfPercent;
    }

    public function getSalaryHike(): float
    {
        return $this->salaryHike;
    }

    public function getInterestRate(): float
    {
        return $this->interestRate;
    }

    public function getCurrentBalance(): float
    {
        return $this->currentBalance;
    }

    public function isCapEps(): bool
    {
        return $this->capEps;
    }

    /**
     * @return array<string, mixed>
     */
    public function toTemplateData(): array
    {
        return [
            'epf_basic_salary' => $this->basicSalary,
            'epf_current_age' => $this->currentAge,
            'epf_retirement_age' => $this->retirementAge,
            'epf_vpf_percent' => $this->vpfPercent,
            'epf_salary_hike' => $this->salaryHike,
            'epf_interest_rate' => $this->interestRate,
            'epf_current_balance' => $this->currentBalance,
            'epf_cap_eps' => $this->capEps ? 1 : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->toTemplateData();
    }
}
