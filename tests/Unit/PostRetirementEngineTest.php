<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\PostRetirementEngine;
use PHPUnit\Framework\TestCase;

class PostRetirementEngineTest extends TestCase
{
    public function testDefaultBaselineCalculation(): void
    {
        // 50L existing, 15L gratuity, 40L NPS, 25k pension, 75k expense, 8.5% ROI, 6% inflation, age 60, horizon 30
        $res = PostRetirementEngine::calculate(
            5000000.0,
            1500000.0,
            4000000.0,
            25000.0,
            75000.0,
            8.5,
            6.0,
            60,
            30
        );

        $this->assertEquals(10500000.0, $res['total_initial_corpus']);
        $this->assertEquals(50000.0, $res['initial_monthly_swp']);
        $this->assertEquals(5.71, $res['initial_swr_percent']);
        $this->assertFalse($res['is_perpetual']);
        $this->assertEquals(19.7, $res['depletion_year']);
        $this->assertEquals(79, $res['depletion_age']);

        // 3-Bucket Allocation Verification
        $this->assertEquals(1800000.0, $res['bucket_1_cash']);
        $this->assertEquals(4200000.0, $res['bucket_2_income']);
        $this->assertEquals(4500000.0, $res['bucket_3_growth']);
        $this->assertEquals(
            $res['total_initial_corpus'],
            $res['bucket_1_cash'] + $res['bucket_2_income'] + $res['bucket_3_growth']
        );

        $this->assertCount(30, $res['schedule']);
        $firstYear = $res['schedule'][0];
        $this->assertEquals(1, $firstYear['year']);
        $this->assertEquals(61, $firstYear['age']);
        $this->assertEquals(10500000.0, $firstYear['opening_balance']);
        $this->assertEquals(600000.0, $firstYear['annual_swp_drawn']);
        $this->assertEquals(300000.0, $firstYear['annual_pension_received']);
    }

    public function testPerpetualLongevityScenario(): void
    {
        // 2 Cr existing, 20L gratuity, 50L NPS, 40k pension, 60k expense, 9% ROI, 5% inflation, age 58, horizon 35
        $res = PostRetirementEngine::calculate(
            20000000.0,
            2000000.0,
            5000000.0,
            40000.0,
            60000.0,
            9.0,
            5.0,
            58,
            35
        );

        $this->assertEquals(27000000.0, $res['total_initial_corpus']);
        $this->assertEquals(20000.0, $res['initial_monthly_swp']);
        $this->assertEquals(0.89, $res['initial_swr_percent']);
        $this->assertTrue($res['is_perpetual']);
        $this->assertNull($res['depletion_year']);
        $this->assertNull($res['depletion_age']);
        $this->assertGreaterThan(27000000.0, $res['final_balance']);
    }

    public function testZeroPensionScenario(): void
    {
        // 60L existing, 10L gratuity, 30L NPS, 0 pension, 70k expense, 8% ROI, 6% inflation, age 60, horizon 25
        $res = PostRetirementEngine::calculate(
            6000000.0,
            1000000.0,
            3000000.0,
            0.0,
            70000.0,
            8.0,
            6.0,
            60,
            25
        );

        $this->assertEquals(10000000.0, $res['total_initial_corpus']);
        $this->assertEquals(70000.0, $res['initial_monthly_swp']);
        $this->assertEquals(8.4, $res['initial_swr_percent']);
        $this->assertFalse($res['is_perpetual']);
        $this->assertNotNull($res['depletion_year']);
        $this->assertEquals(0.0, $res['schedule'][0]['annual_pension_received']);
    }

    public function testSmallCorpusRapidDepletion(): void
    {
        // 15L existing, 5L gratuity, 0 NPS, 10k pension, 50k expense, 7.5% ROI, 6% inflation, age 62, horizon 20
        $res = PostRetirementEngine::calculate(
            1500000.0,
            500000.0,
            0.0,
            10000.0,
            50000.0,
            7.5,
            6.0,
            62,
            20
        );

        $this->assertEquals(2000000.0, $res['total_initial_corpus']);
        $this->assertEquals(40000.0, $res['initial_monthly_swp']);
        $this->assertFalse($res['is_perpetual']);
        $this->assertLessThanOrEqual(6, $res['depletion_year']);
        $this->assertEquals(0.0, $res['final_balance']);
    }

    public function testClampingAndEdgeCases(): void
    {
        // Negative inputs should clamp to non-negative defaults
        $res = PostRetirementEngine::calculate(
            -100.0,
            -50.0,
            -20.0,
            -10.0,
            500.0, // below min 10000
            2.0,   // below min 4.0
            1.0,   // below min 3.0
            30,    // below min 40
            5      // below min 10
        );

        $this->assertGreaterThanOrEqual(0.0, $res['total_initial_corpus']);
        $this->assertGreaterThanOrEqual(10000.0, $res['initial_monthly_swp']);
        $this->assertCount(10, $res['schedule']); // min horizon is 10
    }
}
