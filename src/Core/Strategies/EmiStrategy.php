<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class EmiStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'emi';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromRequest([], $this->configService);
    }
}
