<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class CompoundInterestStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'compound_interest';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromLumpsumRequest([], $this->configService);
    }
}
