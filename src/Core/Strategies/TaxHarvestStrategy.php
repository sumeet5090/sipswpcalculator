<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Inputs\CalculatorInputsInterface;
use Core\Inputs\TaxHarvestInputs;
use Core\Math\TaxHarvestEngine;

class TaxHarvestStrategy extends BaseStrategy
{
    public function getType(): string
    {
        return 'tax_harvest';
    }

    public function getInitialInputs(): CalculatorInputsInterface
    {
        $defaults = $this->configService->getCalculatorDefaults();
        $portfolioValue = (float) ($defaults['tax_portfolio_value']['default'] ?? TaxHarvestEngine::DEFAULT_PORTFOLIO_VALUE);
        $investedCost = (float) ($defaults['tax_invested_cost']['default'] ?? TaxHarvestEngine::DEFAULT_INVESTED_COST);
        $expectedReturn = (float) ($defaults['tax_expected_return']['default'] ?? TaxHarvestEngine::DEFAULT_EXPECTED_RETURN);
        $holdingYears = (int) ($defaults['tax_holding_years']['default'] ?? TaxHarvestEngine::DEFAULT_HOLDING_YEARS);
        $grandfatherFmv = (float) ($defaults['tax_grandfather_fmv']['default'] ?? TaxHarvestEngine::DEFAULT_GRANDFATHER_FMV);
        $includeCess = (bool) ($defaults['tax_include_cess']['default'] ?? TaxHarvestEngine::DEFAULT_INCLUDE_CESS);

        return new TaxHarvestInputs(
            $portfolioValue,
            $investedCost,
            $expectedReturn,
            $holdingYears,
            $grandfatherFmv,
            $includeCess
        );
    }

    public function getBenchmarkTemplate(): string
    {
        return 'components/benchmarks/tax-harvest-schedule.twig';
    }

    public function getBenchmarkTitle(): string
    {
        return 'Section 112A LTCG Tax-Harvesting Savings Benchmark Table';
    }
}
