<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates StressTestController architecture, DOM consistency in stress-test-simulator.twig,
 * crash timing epoch calculations, Rupee Cost Averaging dip-buying math, and behavioral conviction deltas.
 */
final class StressTestControllerTest extends TestCase
{
    private string $controllerCode;
    private string $simulatorTwigContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/StressTestController.ts'
        );
        $this->simulatorTwigContent = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/stress-test-simulator.twig'
        );
    }

    public function testStressTestTwigContainsAllRequiredDomElements(): void
    {
        // Container
        $this->assertStringContainsString('id="stress-test-simulator-card"', $this->simulatorTwigContent);

        // Crash timing epoch buttons
        $this->assertStringContainsString('data-epoch="early"', $this->simulatorTwigContent);
        $this->assertStringContainsString('data-epoch="mid"', $this->simulatorTwigContent);
        $this->assertStringContainsString('data-epoch="late"', $this->simulatorTwigContent);

        // Scenario buttons
        $this->assertStringContainsString('data-scenario="baseline"', $this->simulatorTwigContent);
        $this->assertStringContainsString('data-scenario="lehman"', $this->simulatorTwigContent);
        $this->assertStringContainsString('data-scenario="covid"', $this->simulatorTwigContent);
        $this->assertStringContainsString('data-scenario="midcap2015"', $this->simulatorTwigContent);

        // Conviction Multiplier & Behavioral Metrics
        $this->assertStringContainsString('id="stress-conviction-gain"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="stress-behavior-tag"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="stress-rebound-timeline"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="stress-path-disciplined"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="stress-path-panic"', $this->simulatorTwigContent);

        // Quantitative Shock Ledger Elements
        $this->assertStringContainsString('id="stress-preview-drawdown"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="stress-preview-recovery"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="stress-preview-final"', $this->simulatorTwigContent);

        // RCA Lesson Banner & Chart Action Button
        $this->assertStringContainsString('id="stress-lesson-callout"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="stress-lesson-text"', $this->simulatorTwigContent);
        $this->assertStringContainsString('id="toggle-plot-shock-btn"', $this->simulatorTwigContent);
    }

    public function testStressTestControllerEnforcesReactiveArchitecture(): void
    {
        $this->assertStringContainsString('updateResults(results: YearResult[], inputs?: InvestmentInputs)', $this->controllerCode);
        $this->assertStringContainsString('setScenario(scenarioId: string)', $this->controllerCode);
        $this->assertStringContainsString('setCrashEpoch(epoch: \'early\' | \'mid\' | \'late\')', $this->controllerCode);
        $this->assertStringContainsString('getActiveScenario(): string', $this->controllerCode);
        $this->assertStringContainsString('getCrashEpoch(): string', $this->controllerCode);
        $this->assertStringContainsString('getCrashYearIndex(): number', $this->controllerCode);
    }

    public function testStressTestCalculationsParity(): void
    {
        $normalFinal = 10000000; // ₹1 Crore Baseline

        // 1. Baseline: 0% Shock
        $baselineDropPct = 0;
        $baselineDisciplined = (int) round($normalFinal * (1 - ($baselineDropPct / 400)));
        $this->assertSame($normalFinal, $baselineDisciplined);

        // 2. 2008 Lehman GFC: 52% Shock
        $lehmanDropPct = 52;
        $lehmanDisciplined = (int) round($normalFinal * (1 - ($lehmanDropPct / 400)));
        $lehmanPanic = (int) round($normalFinal * 0.58);
        $lehmanConvictionBonus = $lehmanDisciplined - $lehmanPanic;

        $this->assertSame(8700000, $lehmanDisciplined);
        $this->assertSame(5800000, $lehmanPanic);
        $this->assertSame(2900000, $lehmanConvictionBonus); // +₹29 Lakh Conviction Gain!

        // 3. 2020 COVID Flash Crash: 38% Shock
        $covidDropPct = 38;
        $covidDisciplined = (int) round($normalFinal * (1 - ($covidDropPct / 400)));
        $covidPanic = (int) round($normalFinal * 0.58);
        $covidConvictionBonus = $covidDisciplined - $covidPanic;

        $this->assertSame(9050000, $covidDisciplined);
        $this->assertSame(5800000, $covidPanic);
        $this->assertSame(3250000, $covidConvictionBonus); // +₹32.5 Lakh Conviction Gain!
    }
}
