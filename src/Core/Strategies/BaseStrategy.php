<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

abstract class BaseStrategy implements CalculatorStrategyInterface
{
    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromRequest([]);
    }

    public function getCorpus(InvestmentInputs $inputs): float
    {
        return 0.0;
    }
}
