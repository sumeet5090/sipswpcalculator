<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class PpfStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'ppf';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromLumpsumRequest([], $this->configService);
    }
}
