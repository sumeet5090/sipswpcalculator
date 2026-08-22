<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\AdminDashboardPresenter;
use PHPUnit\Framework\TestCase;

class AdminDashboardPresenterTest extends TestCase
{
    private AdminDashboardPresenter $presenter;

    protected function setUp(): void
    {
        $this->presenter = new AdminDashboardPresenter();
    }

    public function testFormatForViewWithEmptyStats(): void
    {
        $viewData = $this->presenter->formatForView([]);

        $this->assertSame(0, $viewData['totalCalculations']);
        $this->assertSame('0.0', $viewData['avgStepUpPct']);
        $this->assertSame(0, $viewData['stepUpSIP']);
        $this->assertSame(0, $viewData['flatSIP']);
        $this->assertSame('0.0', $viewData['tableViewEngagement']);
        $this->assertSame(0.0, $viewData['avgFinalCorpus']);
        $this->assertSame('0.00', $viewData['avgWealthMultiplier']);
        $this->assertSame('0.0', $viewData['b2bAdvisorRate']);
        $this->assertSame('0.0', $viewData['inflationRate']);
        $this->assertSame('1.0', $viewData['avgIterations']);
        $this->assertSame('0.0', $viewData['avgScrollDepth']);
        $this->assertSame(0, $viewData['avgDwellTime']);

        $this->assertSame('[]', $viewData['volumeLabels']);
        $this->assertSame('[]', $viewData['volumeData']);
        $this->assertSame('[]', $viewData['currencyLabels']);
        $this->assertSame('[]', $viewData['currencyData']);
        $this->assertSame('[]', $viewData['currencyColorsJson']);
        $this->assertSame('[0,0]', $viewData['stepUpDoughnutData']);
        $this->assertSame('[]', $viewData['durationLabels']);
        $this->assertSame('[]', $viewData['durationData']);
        $this->assertSame('[]', $viewData['ambitionLabels']);
        $this->assertSame('[]', $viewData['ambitionData']);
        $this->assertSame('[]', $viewData['deviceLabels']);
        $this->assertSame('[]', $viewData['deviceData']);
        $this->assertSame('[]', $viewData['goalModeLabels']);
        $this->assertSame('[]', $viewData['goalModeData']);
        $this->assertSame('[]', $viewData['referrerLabels']);
        $this->assertSame('[]', $viewData['referrerData']);
        $this->assertSame('[]', $viewData['studioTabLabels']);
        $this->assertSame('[]', $viewData['studioTabData']);
        $this->assertSame('[]', $viewData['strategyStarterLabels']);
        $this->assertSame('[]', $viewData['strategyStarterData']);
    }

    public function testFormatForViewWithPopulatedStats(): void
    {
        $stats = [
            'totalCalculations'   => 150,
            'avgStepUpPct'        => 10.5,
            'stepUpSIP'           => 100,
            'flatSIP'             => 50,
            'tableViewEngagement' => 75.2,
            'avgFinalCorpus'      => 35000000.0,
            'avgWealthMultiplier' => 3.45,
            'b2bAdvisorRate'      => 12.0,
            'inflationRate'       => 45.0,
            'avgIterations'       => 4.2,
            'avgScrollDepth'      => 82.5,
            'avgDwellTime'        => 95.0,
            'dailyVolume'         => [
                ['day' => '2026-08-20', 'cnt' => '40'],
                ['day' => '2026-08-21', 'cnt' => '110'],
            ],
            'currencyDist'        => [
                ['currency' => 'INR', 'cnt' => '140'],
                ['currency' => 'USD', 'cnt' => '10'],
            ],
            'durationDist'        => [
                ['bucket' => '10-15y', 'cnt' => '60'],
                ['bucket' => '20-25y', 'cnt' => '90'],
            ],
            'ambitionBuckets'     => [
                ['goal_bucket' => '1-5 Cr', 'cnt' => '80'],
                ['goal_bucket' => '5-10 Cr', 'cnt' => '70'],
            ],
            'deviceDist'          => [
                ['device' => 'mobile', 'cnt' => '100'],
                ['device' => 'desktop', 'cnt' => '50'],
            ],
            'goalModeDist'        => [
                ['mode' => 'grow', 'cnt' => '110'],
                ['mode' => 'target', 'cnt' => '40'],
            ],
            'referrerDist'        => [
                ['ref' => 'google_organic', 'cnt' => '85'],
                ['ref' => 'direct', 'cnt' => '65'],
            ],
            'studioTabDist'       => [
                ['tab' => 'city_benchmark', 'cnt' => '90'],
                ['tab' => 'milestone_roadmap', 'cnt' => '60'],
            ],
            'strategyStarterDist' => [
                ['preset' => 'first_crore', 'cnt' => '70'],
                ['preset' => 'fire_retirement', 'cnt' => '50'],
            ],
        ];

        $viewData = $this->presenter->formatForView($stats);

        $this->assertSame(150, $viewData['totalCalculations']);
        $this->assertSame('10.5', $viewData['avgStepUpPct']);
        $this->assertSame(100, $viewData['stepUpSIP']);
        $this->assertSame(50, $viewData['flatSIP']);
        $this->assertSame('75.2', $viewData['tableViewEngagement']);
        $this->assertSame(35000000.0, $viewData['avgFinalCorpus']);
        $this->assertSame('3.45', $viewData['avgWealthMultiplier']);
        $this->assertSame('12.0', $viewData['b2bAdvisorRate']);
        $this->assertSame('45.0', $viewData['inflationRate']);
        $this->assertSame('4.2', $viewData['avgIterations']);
        $this->assertSame('82.5', $viewData['avgScrollDepth']);
        $this->assertSame(95, $viewData['avgDwellTime']);

        $this->assertStringContainsString('2026-08-20', $viewData['volumeLabels']);
        $this->assertStringContainsString('2026-08-21', $viewData['volumeLabels']);
        $this->assertSame('[40,110]', $viewData['volumeData']);
        $this->assertSame('[100,50]', $viewData['stepUpDoughnutData']);
        $this->assertStringContainsString('Mobile', $viewData['deviceLabels']);
        $this->assertStringContainsString('Desktop', $viewData['deviceLabels']);
        $this->assertSame('[100,50]', $viewData['deviceData']);
        $this->assertStringContainsString('Google Organic', $viewData['referrerLabels']);
        $this->assertSame('[85,65]', $viewData['referrerData']);
        $this->assertStringContainsString('City Benchmark', $viewData['studioTabLabels']);
        $this->assertSame('[90,60]', $viewData['studioTabData']);
        $this->assertStringContainsString('First Crore', $viewData['strategyStarterLabels']);
        $this->assertSame('[70,50]', $viewData['strategyStarterData']);
    }

    public function testJsonEncodingEscapesScriptBreakouts(): void
    {
        $stats = [
            'deviceDist' => [
                ['device' => '</script><script>alert(1)</script>', 'cnt' => 1],
            ],
        ];

        $viewData = $this->presenter->formatForView($stats);

        $this->assertStringNotContainsString('</script>', $viewData['deviceLabels']);
        $this->assertStringContainsString('\u003C', $viewData['deviceLabels']);
    }
}
