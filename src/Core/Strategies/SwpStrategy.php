<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class SwpStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'swp';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromSwpRequest([]);
    }

    public function getCorpus(InvestmentInputs $inputs): float
    {
        return $inputs->getLumpsum();
    }
}
