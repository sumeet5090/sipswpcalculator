<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\InvestmentInputs;

class ComboStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'combo';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        return InvestmentInputs::fromRequest(['enable_swp' => true], $this->configService);
    }
}
