<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class TargetCorpusStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'target_corpus';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromRequest([], $this->configService);
    }
}
