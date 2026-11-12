<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\FdInputs;

class FdStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'fd';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $amount = (float) ($defaults['fd_principal']['default'] ?? $defaults['fd_amount']['default'] ?? 500000.0);
        $rate = (float) ($defaults['fd_rate']['default'] ?? 7.0);
        $years = (float) ($defaults['fd_years']['default'] ?? 3.0);
        $compoundingFrequency = (int) ($defaults['fd_compounding_frequency']['default'] ?? 4);
        $isSenior = (bool) ($defaults['fd_senior']['default'] ?? false);
        $payoutFrequency = (string) ($defaults['fd_frequency']['default'] ?? 'cumulative');

        return new FdInputs($amount, $rate, $years, $compoundingFrequency, $isSenior, $payoutFrequency);
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/fd-compounding.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Bank Fixed Deposit Compounding & Maturity Yield Matrix';
    }
}
