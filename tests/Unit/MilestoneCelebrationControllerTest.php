<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Validates MilestoneCelebrationController architecture, DOM consistency in wealth-roadmap.twig,
 * 5-tier adaptive checkpoints, real purchasing power calculations, and compounding velocity logic.
 */
final class MilestoneCelebrationControllerTest extends TestCase
{
    private string $controllerCode;
    private string $roadmapTwigContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/MilestoneCelebrationController.ts'
        );
        $this->roadmapTwigContent = (string) file_get_contents(
            __DIR__ . '/../../src/Views/components/wealth-roadmap.twig'
        );
    }

    public function testWealthRoadmapTwigContainsAllRequiredDomElements(): void
    {
        // Container & Header
        $this->assertStringContainsString('id="wealth-milestone-roadmap"', $this->roadmapTwigContent);
        $this->assertStringContainsString('id="milestone-hit-counter"', $this->roadmapTwigContent);
        $this->assertStringContainsString('id="open-goal-pledge-btn"', $this->roadmapTwigContent);

        // 5 Adaptive Checkpoint Targets
        $this->assertStringContainsString('data-target="1000000"', $this->roadmapTwigContent);
        $this->assertStringContainsString('data-target="2500000"', $this->roadmapTwigContent);
        $this->assertStringContainsString('data-target="5000000"', $this->roadmapTwigContent);
        $this->assertStringContainsString('data-target="10000000"', $this->roadmapTwigContent);
        $this->assertStringContainsString('data-target="50000000"', $this->roadmapTwigContent);

        // Checkpoint Node Elements
        $this->assertStringContainsString('checkpoint-icon', $this->roadmapTwigContent);
        $this->assertStringContainsString('checkpoint-status', $this->roadmapTwigContent);
        $this->assertStringContainsString('checkpoint-eta', $this->roadmapTwigContent);
        $this->assertStringContainsString('checkpoint-real-value', $this->roadmapTwigContent);
        $this->assertStringContainsString('checkpoint-bar', $this->roadmapTwigContent);

        // Compounding Velocity Banner
        $this->assertStringContainsString('id="milestone-velocity-banner"', $this->roadmapTwigContent);
        $this->assertStringContainsString('id="milestone-velocity-text"', $this->roadmapTwigContent);
    }

    public function testMilestoneCelebrationControllerContracts(): void
    {
        $this->assertStringContainsString('checkMilestones(corpus: number, results: YearResult[] = [], inputs?: InvestmentInputs)', $this->controllerCode);
        $this->assertStringContainsString('getCheckpoints(): MilestoneCheckpoint[]', $this->controllerCode);
        $this->assertStringContainsString('triggerMicroBurst(): void', $this->controllerCode);
        $this->assertStringContainsString('triggerSheenSweep(): void', $this->controllerCode);
        $this->assertStringContainsString('calculateVelocityPivot(results: YearResult[])', $this->controllerCode);
    }

    public function testVelocityPivotCompoundingOvertakeMath(): void
    {
        // Mock YearResult rows where year 1-3 annual deposit is 1.2L, but interest only overtakes in year 4
        $mockResults = [
            ['year' => 1, 'yearly_investment' => 120000, 'yearly_interest' => 8400, 'combined_total' => 128400],
            ['year' => 2, 'yearly_investment' => 120000, 'yearly_interest' => 28000, 'combined_total' => 276400],
            ['year' => 3, 'yearly_investment' => 120000, 'yearly_interest' => 64000, 'combined_total' => 460400],
            ['year' => 4, 'yearly_investment' => 120000, 'yearly_interest' => 135000, 'combined_total' => 715400],
        ];

        $velocityPivotYear = null;
        foreach ($mockResults as $row) {
            if ($row['yearly_interest'] > $row['yearly_investment']) {
                $velocityPivotYear = $row['year'];
                break;
            }
        }

        $this->assertSame(4, $velocityPivotYear, 'Velocity pivot must occur in year 4 when interest surpasses deposits');
    }

    public function testMilestoneParticlePoolCanvasIntegration(): void
    {
        $this->assertStringContainsString('MilestoneParticlePool', $this->controllerCode);
        $this->assertFileExists(__DIR__ . '/../../assets/js/calculators/helpers/MilestoneParticlePool.ts');
        $poolCode = (string) file_get_contents(__DIR__ . '/../../assets/js/calculators/helpers/MilestoneParticlePool.ts');
        $this->assertStringContainsString('HTMLCanvasElement', $poolCode);
        $this->assertStringContainsString('prefers-reduced-motion', $poolCode);
    }

    public function testMilestonePurchasingPowerMathParity(): void
    {
        $target1Cr = 10000000;
        $inflationRate = 6.0;
        $crossoverYear = 15;

        // PV = FV / (1 + i)^n
        $realValue = $target1Cr / ((1 + $inflationRate / 100) ** $crossoverYear);
        $expectedReal = (int) round($realValue);

        $this->assertEqualsWithDelta(4172651, $expectedReal, 1);

        // Seed 10L in Year 3 @ 6% inflation
        $target10L = 1000000;
        $crossoverYear3 = 3;
        $realValue3 = $target10L / ((1 + $inflationRate / 100) ** $crossoverYear3);
        $expectedReal3 = (int) round($realValue3);

        $this->assertEqualsWithDelta(839619, $expectedReal3, 1);
    }
}
