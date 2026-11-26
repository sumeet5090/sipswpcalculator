<?php

declare(strict_types=1);

namespace Core\Inputs;

use Core\Math\TaxHarvestEngine;

/**
 * Immutable Typed Input Value Object for Section 112A LTCG Tax & Harvesting Calculator.
 */
final class TaxHarvestInputs implements CalculatorInputsInterface
{
    private float $portfolioValue;
    private float $investedCost;
    private float $expectedReturn;
    private int $holdingYears;
    private float $grandfatherFmv;
    private bool $includeCess;

    public function __construct(
        float $portfolioValue,
        float $investedCost,
        float $expectedReturn = TaxHarvestEngine::DEFAULT_EXPECTED_RETURN,
        int $holdingYears = TaxHarvestEngine::DEFAULT_HOLDING_YEARS,
        float $grandfatherFmv = TaxHarvestEngine::DEFAULT_GRANDFATHER_FMV,
        bool $includeCess = TaxHarvestEngine::DEFAULT_INCLUDE_CESS
    ) {
        $this->portfolioValue = max(TaxHarvestEngine::MIN_PORTFOLIO_VALUE, min(TaxHarvestEngine::MAX_PORTFOLIO_VALUE, $portfolioValue));
        $this->investedCost = max(TaxHarvestEngine::MIN_INVESTED_COST, min(TaxHarvestEngine::MAX_INVESTED_COST, $investedCost));
        $this->expectedReturn = max(TaxHarvestEngine::MIN_EXPECTED_RETURN, min(TaxHarvestEngine::MAX_EXPECTED_RETURN, $expectedReturn));
        $this->holdingYears = max(TaxHarvestEngine::MIN_HOLDING_YEARS, min(TaxHarvestEngine::MAX_HOLDING_YEARS, $holdingYears));
        $this->grandfatherFmv = max(TaxHarvestEngine::MIN_GRANDFATHER_FMV, min(TaxHarvestEngine::MAX_GRANDFATHER_FMV, $grandfatherFmv));
        $this->includeCess = $includeCess;
    }

    public function getPortfolioValue(): float
    {
        return $this->portfolioValue;
    }

    public function getInvestedCost(): float
    {
        return $this->investedCost;
    }

    public function getExpectedReturn(): float
    {
        return $this->expectedReturn;
    }

    public function getHoldingYears(): int
    {
        return $this->holdingYears;
    }

    public function getGrandfatherFmv(): float
    {
        return $this->grandfatherFmv;
    }

    public function isIncludeCess(): bool
    {
        return $this->includeCess;
    }

    /**
     * @return array<string, mixed>
     */
    public function toTemplateData(): array
    {
        return [
            'tax_portfolio_value' => $this->portfolioValue,
            'tax_invested_cost' => $this->investedCost,
            'tax_expected_return' => $this->expectedReturn,
            'tax_holding_years' => $this->holdingYears,
            'tax_grandfather_fmv' => $this->grandfatherFmv,
            'tax_include_cess' => $this->includeCess ? 1 : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->toTemplateData();
    }
}
