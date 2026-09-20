<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\InflationInputs;

class InflationStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'inflation';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $amount = (float) ($defaults['inf_amount']['default'] ?? 50000.0);
        $rate = (float) ($defaults['inf_rate']['default'] ?? 6.0);
        $years = (int) ($defaults['inf_years']['default'] ?? 10);

        return new InflationInputs($amount, $rate, $years);
    }
}
