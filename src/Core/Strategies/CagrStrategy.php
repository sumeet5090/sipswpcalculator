<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CagrInputs;
use Core\Inputs\CalculatorInputsInterface;

class CagrStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'cagr';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $initial = (float) ($defaults['cagr_initial']['default'] ?? 100000.0);
        $final = (float) ($defaults['cagr_final']['default'] ?? 250000.0);
        $years = (float) ($defaults['cagr_years']['default'] ?? 5.0);

        return new CagrInputs($initial, $final, $years);
    }
}
