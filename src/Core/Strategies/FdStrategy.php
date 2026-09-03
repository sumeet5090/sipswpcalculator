<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class FdStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'fd';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromLumpsumRequest([], $this->configService);
    }
}
