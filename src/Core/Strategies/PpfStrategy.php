<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\PpfInputs;

class PpfStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'ppf';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $annual = (float) ($defaults['ppf_annual_investment']['default'] ?? 150000.0);
        $rate = (float) ($defaults['ppf_rate']['default'] ?? 7.1);
        $years = (int) ($defaults['ppf_years']['default'] ?? 15);

        return new PpfInputs($annual, $rate, $years);
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/ppf-maturity.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Public Provident Fund (PPF) 15 to 30 Year Maturity Schedule (7.1% EEE)';
    }
}
