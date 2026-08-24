<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates Chart Card (chart-visualization.twig) Two-Tier Command Deck, Zero-CLS Telemetry HUD,
 * canvas container, tactile mobile timeline scrubber, and accessibility anchors.
 */
final class ChartCardTest extends TestCase
{
    private string $chartTwigContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chartTwigContent = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/chart-visualization.twig'
        );
    }

    public function testChartCardContainsAllTwoTierCommandDeckElements(): void
    {
        // View Mode Switcher
        $this->assertStringContainsString('id="chart-view-line"', $this->chartTwigContent);
        $this->assertStringContainsString('id="chart-view-donut"', $this->chartTwigContent);

        // Utility Actions
        $this->assertStringContainsString('id="snapshot-scenario-btn"', $this->chartTwigContent);
        $this->assertStringContainsString('id="open-tax-waterfall-btn"', $this->chartTwigContent);

        // Institutional Seal & Active Indicator
        $this->assertStringContainsString('AMFI Aligned', $this->chartTwigContent);
        $this->assertStringContainsString('id="active-lens-indicator"', $this->chartTwigContent);

        // Analytical Lenses
        $this->assertStringContainsString('id="overlay-chip-corridor"', $this->chartTwigContent);
        $this->assertStringContainsString('id="overlay-chip-post-tax"', $this->chartTwigContent);
        $this->assertStringContainsString('id="overlay-chip-wealth-map"', $this->chartTwigContent);

        // Telemetry Pills
        $this->assertStringContainsString('id="corridor-telemetry-pill"', $this->chartTwigContent);
        $this->assertStringContainsString('id="tax-telemetry-pill"', $this->chartTwigContent);
        $this->assertStringContainsString('id="wealth-map-telemetry-pill"', $this->chartTwigContent);
    }

    public function testChartCardTelemetryHudAndCanvasContainer(): void
    {
        // Zero-CLS HUD
        $this->assertStringContainsString('id="chart-telemetry-hud"', $this->chartTwigContent);
        $this->assertStringContainsString('id="hud-status-dot"', $this->chartTwigContent);
        $this->assertStringContainsString('id="ribbon-inspect-year"', $this->chartTwigContent);
        $this->assertStringContainsString('id="ribbon-inspect-invested"', $this->chartTwigContent);
        $this->assertStringContainsString('id="ribbon-inspect-gains"', $this->chartTwigContent);
        $this->assertStringContainsString('id="ribbon-inspect-corpus"', $this->chartTwigContent);

        // Canvas & Keyboard Scrutiny Container
        $this->assertStringContainsString('id="chart-canvas-container"', $this->chartTwigContent);
        $this->assertStringContainsString('tabindex="0"', $this->chartTwigContent);
        $this->assertStringContainsString('id="corpusChart"', $this->chartTwigContent);
        $this->assertStringContainsString('id="chart-inspection-ribbon"', $this->chartTwigContent);
    }

    public function testChartCardMobileScrubberAndAccessibleTable(): void
    {
        // Mobile Jog-Dial Scrubber Deck
        $this->assertStringContainsString('id="mobile-chart-scrubber-container"', $this->chartTwigContent);
        $this->assertStringContainsString('id="mobile-chart-scrubber"', $this->chartTwigContent);
        $this->assertStringContainsString('id="scrubber-active-indicator"', $this->chartTwigContent);
        $this->assertStringContainsString('id="scrubber-max-indicator"', $this->chartTwigContent);

        // Accessible Screen-Reader Ledger
        $this->assertStringContainsString('id="a11y-chart-table-body"', $this->chartTwigContent);

        // Baseline Benchmark Chips
        $this->assertStringContainsString('data-benchmark="none"', $this->chartTwigContent);
        $this->assertStringContainsString('data-benchmark="nifty"', $this->chartTwigContent);
        $this->assertStringContainsString('data-benchmark="gold"', $this->chartTwigContent);
        $this->assertStringContainsString('data-benchmark="fd"', $this->chartTwigContent);
    }
}
