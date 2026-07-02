<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentInputs;

class InvestmentInputsTest extends TestCase
{
    /**
     * Test instantiation from request with empty data defaults.
     */
    public function testDefaultInputs(): void
    {
        $inputs = InvestmentInputs::fromRequest([]);

        $this->assertEquals(10000.0, $inputs->getSip());
        $this->assertEquals(20, $inputs->getYears());
        $this->assertEquals(12.0, $inputs->getRate());
        $this->assertEquals(10.0, $inputs->getStepup());
        $this->assertFalse($inputs->isSwpEnabled());
        $this->assertEquals(5000.0, $inputs->getSwpWithdrawal());
        $this->assertEquals(6.0, $inputs->getSwpStepup());
        $this->assertEquals(20, $inputs->getSwpYears());
        $this->assertEquals(0.0, $inputs->getLumpsum());
        $this->assertEquals(8.0, $inputs->getSwpRate());
    }

    /**
     * Test that parameters within bounds are correctly assigned.
     */
    public function testValidInputs(): void
    {
        $data = [
            'sip' => 25000.0,
            'years' => 15,
            'rate' => 13.5,
            'stepup' => 8.0,
            'enable_swp' => 1,
            'swp_withdrawal' => 12000.0,
            'swp_stepup' => 5.0,
            'swp_years' => 25,
            'lumpsum' => 100000.0,
            'swp_rate' => 7.5
        ];

        $inputs = InvestmentInputs::fromRequest($data);

        $this->assertEquals(25000.0, $inputs->getSip());
        $this->assertEquals(15, $inputs->getYears());
        $this->assertEquals(13.5, $inputs->getRate());
        $this->assertEquals(8.0, $inputs->getStepup());
        $this->assertTrue($inputs->isSwpEnabled());
        $this->assertEquals(12000.0, $inputs->getSwpWithdrawal());
        $this->assertEquals(5.0, $inputs->getSwpStepup());
        $this->assertEquals(25, $inputs->getSwpYears());
        $this->assertEquals(100000.0, $inputs->getLumpsum());
        $this->assertEquals(7.5, $inputs->getSwpRate());
    }

    /**
     * Test that inputs above max limits are clamped correctly.
     */
    public function testUpperClamping(): void
    {
        $data = [
            'sip' => 2000000.0,      // Max is 1,000,000
            'years' => 100,          // Max is 50
            'rate' => 45.0,          // Max is 30
            'stepup' => 75.0,        // Max is 50
            'swp_withdrawal' => 1500000.0, // Max is 1,000,000
            'swp_stepup' => 35.0,    // Max is 20
            'swp_years' => 90,        // Max is 50
            'lumpsum' => 20000000.0,  // Max is 10,00,0000
            'swp_rate' => 45.0       // Max is 30
        ];

        $inputs = InvestmentInputs::fromRequest($data);

        $this->assertEquals(1000000.0, $inputs->getSip());
        $this->assertEquals(50, $inputs->getYears());
        $this->assertEquals(30.0, $inputs->getRate());
        $this->assertEquals(50.0, $inputs->getStepup());
        $this->assertEquals(1000000.0, $inputs->getSwpWithdrawal());
        $this->assertEquals(20.0, $inputs->getSwpStepup());
        $this->assertEquals(50, $inputs->getSwpYears());
        $this->assertEquals(10000000.0, $inputs->getLumpsum());
        $this->assertEquals(30.0, $inputs->getSwpRate());
    }

    /**
     * Test that inputs below min limits are clamped correctly.
     */
    public function testLowerClamping(): void
    {
        $data = [
            'sip' => 100.0,         // Min is 500
            'years' => 0,           // Min is 1
            'rate' => -5.0,         // Min is 0.1
            'stepup' => -10.0,      // Min is 0
            'swp_withdrawal' => -500.0, // Min is 0
            'swp_stepup' => -2.0,   // Min is 0
            'swp_years' => -5,       // Min is 1
            'lumpsum' => -5000.0,    // Min is 0
            'swp_rate' => -2.0       // Min is 0.1
        ];

        $inputs = InvestmentInputs::fromRequest($data);

        $this->assertEquals(500.0, $inputs->getSip());
        $this->assertEquals(1, $inputs->getYears());
        $this->assertEquals(0.1, $inputs->getRate());
        $this->assertEquals(0.0, $inputs->getStepup());
        $this->assertEquals(0.0, $inputs->getSwpWithdrawal());
        $this->assertEquals(0.0, $inputs->getSwpStepup());
        $this->assertEquals(1, $inputs->getSwpYears());
        $this->assertEquals(0.0, $inputs->getLumpsum());
        $this->assertEquals(0.1, $inputs->getSwpRate());
    }

    /**
     * Test that numeric strings are cast and handled correctly.
     */
    public function testStringAndTypeCasting(): void
    {
        $data = [
            'sip' => '15000',
            'years' => '12',
            'rate' => '14.25',
            'stepup' => '5',
            'enable_swp' => 'true',
            'swp_withdrawal' => '8000',
            'swp_stepup' => '4.5',
            'swp_years' => '18',
            'lumpsum' => '50000',
            'swp_rate' => '6.75'
        ];

        $inputs = InvestmentInputs::fromRequest($data);

        $this->assertEquals(15000.0, $inputs->getSip());
        $this->assertEquals(12, $inputs->getYears());
        $this->assertEquals(14.25, $inputs->getRate());
        $this->assertEquals(5.0, $inputs->getStepup());
        $this->assertTrue($inputs->isSwpEnabled());
        $this->assertEquals(8000.0, $inputs->getSwpWithdrawal());
        $this->assertEquals(4.5, $inputs->getSwpStepup());
        $this->assertEquals(18, $inputs->getSwpYears());
        $this->assertEquals(50000.0, $inputs->getLumpsum());
        $this->assertEquals(6.75, $inputs->getSwpRate());
    }
}
