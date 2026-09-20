<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\CompoundInterestInputs;

class CompoundInterestStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'compound_interest';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $principal = (float) ($defaults['ci_principal']['default'] ?? 500000.0);
        $rate = (float) ($defaults['ci_rate']['default'] ?? 12.0);
        $years = (int) ($defaults['ci_years']['default'] ?? 10);
        $frequency = (int) ($defaults['ci_frequency']['default'] ?? 12);

        return new CompoundInterestInputs($principal, $rate, $years, $frequency);
    }
}
