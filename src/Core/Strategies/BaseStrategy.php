<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\InvestmentInputs;
use Services\ConfigService;

abstract class BaseStrategy implements CalculatorStrategyInterface
{
    protected ConfigService $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function getInitialInputs(): InvestmentInputs
    {
        return InvestmentInputs::fromRequest([], $this->configService);
    }

    public function getCorpus(InvestmentInputs $inputs): float
    {
        return 0.0;
    }
}
