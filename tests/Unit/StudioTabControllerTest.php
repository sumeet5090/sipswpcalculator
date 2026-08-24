<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates StudioTabController contracts, ARIA keyboard navigation,
 * real-time telemetry badge containers, SVG icons, and analytical studio DOM consistency.
 */
final class StudioTabControllerTest extends TestCase
{
    private string $controllerCode;
    private string $studioTwigContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/controllers/StudioTabController.ts');
        $this->studioTwigContent = (string) file_get_contents(__DIR__ . '/../../src/Views/components/analytical-studio.twig');
    }

    public function testStudioTabControllerEnforcesAriaAndDomConventions(): void
    {
        $this->assertStringContainsString('studio-tabs-nav', $this->controllerCode);
        $this->assertStringContainsString('.studio-tab-btn', $this->controllerCode);
        $this->assertStringContainsString('.studio-tab-panel', $this->controllerCode);
        $this->assertStringContainsString("tab.setAttribute('aria-selected', isSelected ? 'true' : 'false')", $this->controllerCode);
        $this->assertStringContainsString("tab.setAttribute('tabindex', isSelected ? '0' : '-1')", $this->controllerCode);
    }

    public function testStudioTabControllerImplementsAccessibleKeyboardNavigation(): void
    {
        $this->assertStringContainsString("e.key === 'ArrowRight'", $this->controllerCode);
        $this->assertStringContainsString("e.key === 'ArrowLeft'", $this->controllerCode);
        $this->assertStringContainsString("e.key === 'Home'", $this->controllerCode);
        $this->assertStringContainsString("e.key === 'End'", $this->controllerCode);
    }

    public function testStudioTabControllerImplementsMobileScrollCentering(): void
    {
        $this->assertStringContainsString("tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' })", $this->controllerCode);
    }

    public function testStudioTabControllerImplementsTelemetryUpdateContract(): void
    {
        $this->assertStringContainsString('interface StudioTelemetryData', $this->controllerCode);
        $this->assertStringContainsString('updateTelemetry(data: StudioTelemetryData)', $this->controllerCode);
        $this->assertStringContainsString('tab-telemetry-breakdown', $this->controllerCode);
        $this->assertStringContainsString('tab-telemetry-fire', $this->controllerCode);
        $this->assertStringContainsString('tab-telemetry-milestones', $this->controllerCode);
        $this->assertStringContainsString('tab-telemetry-stress', $this->controllerCode);
        $this->assertStringContainsString('tab-telemetry-rebalance', $this->controllerCode);
    }

    public function testAnalyticalStudioContainsAllRequiredTabsAndMatchingPanels(): void
    {
        $tabs = [
            'tab-yearly-breakdown' => 'panel-yearly-breakdown',
            'tab-milestone-roadmap' => 'panel-milestone-roadmap',
            'tab-stress-test' => 'panel-stress-test',
            'tab-city-benchmark' => 'panel-city-benchmark',
            'tab-asset-rebalance' => 'panel-asset-rebalance',
        ];

        foreach ($tabs as $tabId => $panelId) {
            $this->assertStringContainsString("id=\"{$tabId}\"", $this->studioTwigContent);
            $this->assertStringContainsString("aria-controls=\"{$panelId}\"", $this->studioTwigContent);
            $this->assertStringContainsString("id=\"{$panelId}\"", $this->studioTwigContent);
            $this->assertStringContainsString("aria-labelledby=\"{$tabId}\"", $this->studioTwigContent);
        }
    }

    public function testAnalyticalStudioContainsLiveTelemetryContainers(): void
    {
        $telemetryIds = [
            'tab-telemetry-breakdown',
            'tab-telemetry-fire',
            'tab-telemetry-milestones',
            'tab-telemetry-stress',
            'tab-telemetry-rebalance',
        ];

        foreach ($telemetryIds as $telemetryId) {
            $this->assertStringContainsString("id=\"{$telemetryId}\"", $this->studioTwigContent);
        }
    }

    public function testAnalyticalStudioContainsUtilityDeckAndGradientIndicators(): void
    {
        $this->assertStringContainsString('id="studio-unit-lakh"', $this->studioTwigContent);
        $this->assertStringContainsString('id="studio-unit-exact"', $this->studioTwigContent);
        $this->assertStringContainsString('id="studio-export-pdf-btn"', $this->studioTwigContent);
        $this->assertStringContainsString('bg-gradient-to-r from-white to-transparent', $this->studioTwigContent);
        $this->assertStringContainsString('bg-gradient-to-l from-white to-transparent', $this->studioTwigContent);
    }

    public function testYearlyBreakdownIsDefaultActiveTab(): void
    {
        $this->assertStringContainsString('id="tab-yearly-breakdown" aria-controls="panel-yearly-breakdown" aria-selected="true"', $this->studioTwigContent);
        $this->assertStringContainsString('id="panel-yearly-breakdown" role="tabpanel" aria-labelledby="tab-yearly-breakdown" class="studio-tab-panel', $this->studioTwigContent);
    }
}
