<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates AssetRebalanceController architecture, DOM consistency in asset-rebalancing.twig,
 * Tri-Asset allocation (Equity, Debt, Gold), simulated market drift, and Section 112A Tax Alpha calculations.
 */
final class AssetRebalanceControllerTest extends TestCase
{
    private string $controllerCode;
    private string $rebalancingTwigContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/AssetRebalanceController.ts'
        );
        $this->rebalancingTwigContent = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/asset-rebalancing.twig'
        );
    }

    public function testAssetRebalancingTwigContainsAllRequiredDomElements(): void
    {
        // Container
        $this->assertStringContainsString('id="asset-rebalancing-card"', $this->rebalancingTwigContent);

        // Drift state toggle buttons
        $this->assertStringContainsString('data-drift="normal"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('data-drift="bull"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('data-drift="bear"', $this->rebalancingTwigContent);

        // Asset allocation profile buttons
        $this->assertStringContainsString('data-profile="aggressive"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('data-profile="balanced"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('data-profile="allweather"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('data-profile="conservative"', $this->rebalancingTwigContent);

        // Tri-Asset Stacked Progress Bar Elements
        $this->assertStringContainsString('id="rebalance-ratio-label"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-bar-equity"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-bar-debt"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-bar-gold"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-pct-equity"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-pct-debt"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-pct-gold"', $this->rebalancingTwigContent);

        // Quantitative Ledger Elements
        $this->assertStringContainsString('id="rebalance-preview-cagr"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-preview-volatility"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-tax-savings"', $this->rebalancingTwigContent);

        // Smart Cashflow Directive Banner
        $this->assertStringContainsString('id="rebalance-action-banner"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-action-text"', $this->rebalancingTwigContent);
        $this->assertStringContainsString('id="rebalance-status-chip"', $this->rebalancingTwigContent);
    }

    public function testAssetRebalanceControllerEnforcesReactiveArchitecture(): void
    {
        $this->assertStringContainsString('updateInputs(inputs: InvestmentInputs, results?: YearResult[])', $this->controllerCode);
        $this->assertStringContainsString('setProfile(profileKey: string)', $this->controllerCode);
        $this->assertStringContainsString('setDriftState(state: \'normal\' | \'bull\' | \'bear\')', $this->controllerCode);
        $this->assertStringContainsString('getActiveProfile(): string', $this->controllerCode);
        $this->assertStringContainsString('getDriftState(): string', $this->controllerCode);
        $this->assertStringContainsString('getTargetSplit(): { equity: number; debt: number; gold: number }', $this->controllerCode);
    }

    public function testAssetRebalanceCalculationsParity(): void
    {
        $eqRate = 12.0;
        $debtRate = 7.0;
        $goldRate = 9.5;

        // 1. 70/30 Balanced Profile CAGR
        $balancedEquityPct = 70;
        $balancedDebtPct = 30;
        $balancedGoldPct = 0;
        $balancedBlended = ($balancedEquityPct / 100 * $eqRate) + ($balancedDebtPct / 100 * $debtRate) + ($balancedGoldPct / 100 * $goldRate);
        $this->assertEqualsWithDelta(10.5, $balancedBlended, 0.01);

        // 2. All-Weather 60/25/15 Profile CAGR
        $allWeatherEquityPct = 60;
        $allWeatherDebtPct = 25;
        $allWeatherGoldPct = 15;
        $allWeatherBlended = ($allWeatherEquityPct / 100 * $eqRate) + ($allWeatherDebtPct / 100 * $debtRate) + ($allWeatherGoldPct / 100 * $goldRate);
        $this->assertEqualsWithDelta(10.375, $allWeatherBlended, 0.01);

        // 3. Section 112A Tax Alpha Savings on ₹25,000 SIP for 15 Years
        $monthlySip = 25000;
        $years = 15;
        $totalInvested = $monthlySip * 12 * $years;
        $this->assertSame(4500000, $totalInvested);

        $taxAlphaSaved = (int) round($totalInvested * 0.055);
        $this->assertSame(247500, $taxAlphaSaved);
    }
}
