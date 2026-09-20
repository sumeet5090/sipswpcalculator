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
        return InvestmentInputs::fromSwpRequest([], $this->configService);
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/swp-longevity.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'SWP Withdrawal & Corpus Longevity Benchmark Table';
    }
}
