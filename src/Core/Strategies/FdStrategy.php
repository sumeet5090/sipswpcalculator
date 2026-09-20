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
        $amount = (float) ($defaults['fd_amount']['default'] ?? 100000.0);
        $rate = (float) ($defaults['fd_rate']['default'] ?? 7.0);
        $years = (int) ($defaults['fd_years']['default'] ?? 5);
        $frequency = (int) ($defaults['fd_frequency']['default'] ?? 4);

        return new FdInputs($amount, $rate, $years, $frequency);
    }
}
