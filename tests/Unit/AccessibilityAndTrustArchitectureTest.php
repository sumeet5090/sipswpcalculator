<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Config\ThemeConstants;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigService;

/**
 * Validates UI/UX audit enhancements across Trust Architecture, Accessibility,
 * Elastic Autoscaling, Contextual Alpha Radar, and Lifecycle Bridge.
 */
final class AccessibilityAndTrustArchitectureTest extends TestCase
{
    private ConfigService $config;
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->config = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $this->calculator = new InvestmentCalculator();
    }

    public function testElasticSliderAutoscaleIncrements(): void
    {
        $computeElasticMax = static function (float $val, float $defaultMax): float {
            if ($val <= $defaultMax) {
                return $defaultMax;
            }

            $rawTarget = $val * 1.5;
            if ($rawTarget < 100000) {
                return ceil($rawTarget / 10000) * 10000;
            }
            if ($rawTarget < 1000000) {
                return ceil($rawTarget / 50000) * 50000;
            }
            if ($rawTarget < 10000000) {
                return ceil($rawTarget / 500000) * 500000;
            }
            return ceil($rawTarget / 5000000) * 5000000;
        };

        // Standard default max ₹1,00,000
        $defaultMax = 100000.0;

        // Within bounds
        $this->assertEquals(100000.0, $computeElasticMax(50000.0, $defaultMax));

        // High ticket input of ₹2,50,000 -> target is 3,75,000 -> rounded to 50k increment: 4,00,000
        $this->assertEquals(400000.0, $computeElasticMax(250000.0, $defaultMax));

        // High ticket input of ₹2,00,00,000 -> target is 3,00,00,000 -> rounded to 50L increment: 3,00,00,000
        $this->assertEquals(30000000.0, $computeElasticMax(20000000.0, $defaultMax));
    }

    public function testLifecycleAccumulationBridgeMaturedCorpusSeeding(): void
    {
        $inputs = InvestmentInputs::fromRequest([
            'sip' => 20000,
            'years' => 10,
            'rate' => 12,
            'stepup' => 10,
            'lumpsum' => 0,
            'enable_swp' => false
        ], $this->config);

        $results = $this->calculator->calculate($inputs);
        $lastRow = end($results);
        $maturedCorpus = $lastRow['combined_total'];

        $this->assertGreaterThan(5000000.0, $maturedCorpus, "10-year SIP must generate matured wealth > ₹50L");

        // Seed matured corpus into SWP combined simulation (10 years SIP + 10 years SWP)
        $swpInputs = InvestmentInputs::fromRequest([
            'sip' => 20000,
            'years' => 10,
            'rate' => 12,
            'stepup' => 10,
            'lumpsum' => 0,
            'enable_swp' => true,
            'swp_withdrawal' => 30000,
            'swp_years' => 10,
            'swp_rate' => 8,
            'swp_stepup' => 5
        ], $this->config);

        $swpResults = $this->calculator->calculate($swpInputs);
        $this->assertCount(20, $swpResults);

        $swpLastRow = end($swpResults);
        $this->assertGreaterThan(0.0, $swpLastRow['combined_total'], "SWP starting with matured corpus must sustain across distribution phase");
    }

    public function testContextualRadarPriorityDetermination(): void
    {
        // Case 1: High SWP depletion
        $depletionInputs = InvestmentInputs::fromRequest([
            'sip' => 0,
            'years' => 0,
            'lumpsum' => 1000000, // 10 Lakhs
            'enable_swp' => true,
            'swp_withdrawal' => 50000, // 50k/mo = 6L/yr (60% withdrawal rate)
            'swp_years' => 10,
            'swp_rate' => 6
        ], $this->config);

        $results = $this->calculator->calculate($depletionInputs);
        $hasDepletion = false;
        $depletedYear = null;

        foreach ($results as $row) {
            if ($row['combined_total'] <= 0.0) {
                $hasDepletion = true;
                $depletedYear = $row['year'];
                break;
            }
        }

        $this->assertTrue($hasDepletion, "Aggressive SWP must trigger portfolio depletion warning");
        $this->assertLessThanOrEqual(3, $depletedYear, "10L portfolio at 6L/yr must deplete within 3 years");
    }

    public function testSafeSwpAutoHealAndCorridorBounds(): void
    {
        $inputs = InvestmentInputs::fromValues(
            0.0,
            0,
            0.0,
            0.0,
            true,
            120000.0,
            5.0,
            15,
            5000000.0,
            7.5
        );

        $safeWithdrawal = $this->calculator->calculateSafeSwpWithdrawal($inputs, 5000000.0);
        $this->assertGreaterThan(25000.0, $safeWithdrawal);
        $this->assertLessThan(45000.0, $safeWithdrawal);
    }

    public function testChartVisualizationAccessibilityAndTrustArchitecture(): void
    {
        $templatePath = dirname(__DIR__, 2) . '/src/Views/components/chart-visualization.twig';
        $this->assertFileExists($templatePath);
        $content = (string) file_get_contents($templatePath);

        // Assert morphological grid IDs
        $this->assertStringContainsString('id="summary-cards-grid"', $content);
        $this->assertStringContainsString('id="card-withdrawn"', $content);
        $this->assertStringContainsString('id="summary-invested"', $content);
        $this->assertStringContainsString('id="summary-interest"', $content);
        $this->assertStringContainsString('id="summary-corpus"', $content);

        // Assert AMFI Institutional Seal and Chart Card
        $this->assertStringContainsString('AMFI Aligned', $content);
        $this->assertStringContainsString('id="chart-card"', $content);

        // Assert keyboard accessible canvas container and screen reader ledger table
        $this->assertStringContainsString('id="chart-canvas-container"', $content);
        $this->assertStringContainsString('tabindex="0"', $content);
        $this->assertStringContainsString('id="a11y-chart-table-body"', $content);

        // Assert Master Console View Switcher & Action Hub
        $this->assertStringContainsString('id="chart-view-line"', $content);
        $this->assertStringContainsString('id="chart-view-donut"', $content);
        $this->assertStringContainsString('id="snapshot-scenario-btn"', $content);
        $this->assertStringContainsString('id="open-tax-waterfall-btn"', $content);

        // Assert Analytical Overlays Control Studio chips and telemetry pills
        $this->assertStringContainsString('id="overlay-chip-corridor"', $content);
        $this->assertStringContainsString('id="overlay-chip-post-tax"', $content);
        $this->assertStringContainsString('id="overlay-chip-wealth-map"', $content);
        $this->assertStringContainsString('id="corridor-telemetry-pill"', $content);
        $this->assertStringContainsString('id="tax-telemetry-pill"', $content);
        $this->assertStringContainsString('id="wealth-map-telemetry-pill"', $content);
        $this->assertStringContainsString('id="active-lens-indicator"', $content);

        // Assert Mobile Ergonomic Thumb Scrubber Container
        $this->assertStringContainsString('id="mobile-chart-scrubber-container"', $content);
        $this->assertStringContainsString('id="mobile-chart-scrubber"', $content);
        $this->assertStringContainsString('id="scrubber-active-indicator"', $content);
    }
}
