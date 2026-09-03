<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class InflationStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'inflation';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromRequest([], $this->configService);
    }
}
