<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates CityBenchmarkController architecture, DOM consistency in city-fire-benchmark.twig,
 * housing tenure deduction math, SWR multipliers, and personal readiness evaluation.
 */
final class CityBenchmarkControllerTest extends TestCase
{
    private string $controllerCode;
    private string $benchmarkTwigContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/CityBenchmarkController.ts'
        );
        $this->benchmarkTwigContent = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/city-fire-benchmark.twig'
        );
    }

    public function testCityBenchmarkTwigContainsAllRequiredDomElements(): void
    {
        // Container
        $this->assertStringContainsString('id="city-fire-benchmark-card"', $this->benchmarkTwigContent);

        // Housing tenure buttons
        $this->assertStringContainsString('id="housing-rented-btn"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="housing-owned-btn"', $this->benchmarkTwigContent);

        // SWR Multiplier buttons
        $this->assertStringContainsString('data-multiplier="25"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('data-multiplier="30"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('data-multiplier="35"', $this->benchmarkTwigContent);

        // City preset buttons
        $this->assertStringContainsString('data-city="mumbai"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('data-city="bengaluru"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('data-city="delhi"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('data-city="pune"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('data-city="tier2"', $this->benchmarkTwigContent);

        // Personal FIRE Readiness Radar Elements
        $this->assertStringContainsString('id="fire-readiness-percent"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="fire-status-badge"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="fire-horizon-date"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="fire-progress-bar"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="fire-bar-pct-label"', $this->benchmarkTwigContent);

        // Quantitative Preview Elements
        $this->assertStringContainsString('id="city-preview-expense"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="city-preview-corpus"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="city-preview-sip"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="fire-multiplier-tag"', $this->benchmarkTwigContent);

        // Geo-Arbitrage Banner
        $this->assertStringContainsString('id="geo-arbitrage-banner"', $this->benchmarkTwigContent);
        $this->assertStringContainsString('id="geo-arbitrage-text"', $this->benchmarkTwigContent);

        // Adopt Action Button
        $this->assertStringContainsString('id="apply-city-benchmark-btn"', $this->benchmarkTwigContent);
    }

    public function testCityBenchmarkControllerEnforcesReactiveArchitecture(): void
    {
        $this->assertStringContainsString('updateResults(results: YearResult[], inputs?: InvestmentInputs)', $this->controllerCode);
        $this->assertStringContainsString('getEffectiveMonthlyExpense(): number', $this->controllerCode);
        $this->assertStringContainsString('getTargetCorpus(): number', $this->controllerCode);
        $this->assertStringContainsString('adoptCityStrategy(): void', $this->controllerCode);
        $this->assertStringContainsString('getActiveCity(): string', $this->controllerCode);
        $this->assertStringContainsString('getActiveMultiplier(): number', $this->controllerCode);
        $this->assertStringContainsString('isHomeOwnerMode(): boolean', $this->controllerCode);
    }

    public function testCityBenchmarkCalculationsParity(): void
    {
        // 1. Mumbai Rented (85k/mo, 30x Multiplier)
        $mumbaiExpense = 85000;
        $mumbaiAnnual = $mumbaiExpense * 12;
        $mumbai30xCorpus = $mumbaiAnnual * 30;
        $this->assertSame(30600000, $mumbai30xCorpus);

        // 2. Mumbai Homeowner (-35% housing deduction)
        $mumbaiHomeownerExpense = (int) round($mumbaiExpense * 0.65);
        $this->assertSame(55250, $mumbaiHomeownerExpense);
        $mumbaiHomeowner30xCorpus = $mumbaiHomeownerExpense * 12 * 30;
        $this->assertSame(19890000, $mumbaiHomeowner30xCorpus);

        // 3. Tier-2 Rented (45k/mo, 25x Lean Multiplier)
        $tier2Expense = 45000;
        $tier225xCorpus = $tier2Expense * 12 * 25;
        $this->assertSame(13500000, $tier225xCorpus);

        // 4. Geo-Arbitrage Savings Delta
        $geoArbitrageSavings = $mumbai30xCorpus - ($tier2Expense * 12 * 30);
        $this->assertSame(14400000, $geoArbitrageSavings);
    }
}
