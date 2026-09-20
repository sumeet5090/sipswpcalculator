<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\EmiInputs;

class EmiStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'emi';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $principal = (float) ($defaults['emi_principal']['default'] ?? 3000000.0);
        $rate = (float) ($defaults['emi_rate']['default'] ?? 8.5);
        $years = (int) ($defaults['emi_years']['default'] ?? 20);

        return new EmiInputs($principal, $rate, $years);
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/emi-amortization.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Home & Car Loan Monthly EMI Amortization Matrix (8.5% p.a.)';
    }
}
