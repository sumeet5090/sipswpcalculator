<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;

class LumpsumStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'lumpsum';
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromLumpsumRequest([], $this->configService);
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/lumpsum-growth.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Lumpsum Investment Growth Matrix (AMFI Standard @ 12% CAGR)';
    }
}
