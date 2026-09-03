<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class CagrStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'cagr';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromLumpsumRequest([], $this->configService);
    }
}
