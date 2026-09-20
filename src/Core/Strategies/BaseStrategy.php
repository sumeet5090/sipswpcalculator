<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\InvestmentInputs;
use Services\ConfigServiceInterface;

abstract class BaseStrategy implements CalculatorStrategyInterface
{
    protected ConfigServiceInterface $configService;

    public function __construct(ConfigServiceInterface $configService)
    {
        $this->configService = $configService;
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        return InvestmentInputs::fromRequest([], $this->configService);
    }
}
