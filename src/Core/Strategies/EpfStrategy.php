<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\EpfInputs;
use Core\Math\EpfEngine;

class EpfStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'epf';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $basicSalary = (float) ($defaults['epf_basic_salary']['default'] ?? EpfEngine::DEFAULT_BASIC_SALARY);
        $currentAge = (int) ($defaults['epf_current_age']['default'] ?? EpfEngine::DEFAULT_CURRENT_AGE);
        $retirementAge = (int) ($defaults['epf_retirement_age']['default'] ?? EpfEngine::DEFAULT_RETIREMENT_AGE);
        $vpfPercent = (float) ($defaults['epf_vpf_percent']['default'] ?? EpfEngine::DEFAULT_VPF_PERCENT);
        $salaryHike = (float) ($defaults['epf_salary_hike']['default'] ?? EpfEngine::DEFAULT_SALARY_HIKE);
        $interestRate = (float) ($defaults['epf_interest_rate']['default'] ?? EpfEngine::DEFAULT_INTEREST_RATE);
        $currentBalance = (float) ($defaults['epf_current_balance']['default'] ?? EpfEngine::DEFAULT_CURRENT_BALANCE);
        $capEps = (bool) ($defaults['epf_cap_eps']['default'] ?? EpfEngine::DEFAULT_CAP_EPS);

        return new EpfInputs(
            $basicSalary,
            $currentAge,
            $retirementAge,
            $vpfPercent,
            $salaryHike,
            $interestRate,
            $currentBalance,
            $capEps
        );
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/epf-schedule.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return "Employees' Provident Fund (EPF & VPF) Maturity Benchmark Table";
    }
}
