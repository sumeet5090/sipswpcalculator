<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\GratuityInputs;

class GratuityStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'gratuity';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $salary = (float) ($defaults['gratuity_salary']['default'] ?? 50000.0);
        $years = (int) ($defaults['gratuity_years']['default'] ?? 5);
        $months = (int) ($defaults['gratuity_months']['default'] ?? 0);
        $covered = (bool) ($defaults['gratuity_covered']['default'] ?? true);

        return new GratuityInputs($salary, $years, $months, $covered);
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/gratuity-schedule.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Statutory Gratuity Accrual & Section 10(10) Tax Exemption Schedule';
    }
}
