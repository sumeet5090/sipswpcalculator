<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\InvestmentInputs;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;

class InvestmentInputsTemplateDataTest extends TestCase
{
    private ConfigService $configService;

    protected function setUp(): void
    {
        $this->configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
    }

    public function testToTemplateDataContainsAllRequiredKeys(): void
    {
        $inputs = InvestmentInputs::fromRequest([
            'sip'            => 25000.0,
            'years'          => 12,
            'rate'           => 14.0,
            'stepup'         => 8.0,
            'lumpsum'        => 50000.0,
            'enable_swp'     => true,
            'swp_withdrawal' => 30000.0,
            'swp_years'      => 10,
            'swp_stepup'     => 5.0,
            'swp_rate'       => 7.5,
            'inflation'      => 6.0,
        ], $this->configService);

        $data = $inputs->toTemplateData();

        $this->assertSame(25000.0, $data['sip']);
        $this->assertSame(12, $data['years']);
        $this->assertSame(14.0, $data['rate']);
        $this->assertSame(8.0, $data['stepup']);
        $this->assertSame(50000.0, $data['lumpsum']);
        $this->assertSame(50000.0, $data['corpus']);
        $this->assertTrue($data['enable_swp']);
        $this->assertSame(30000.0, $data['swp_withdrawal']);
        $this->assertSame(10, $data['swp_years_input']);
        $this->assertSame(5.0, $data['swp_stepup']);
        $this->assertSame(7.5, $data['swp_rate']);
        $this->assertSame(6.0, $data['inflation']);
    }

    public function testFromLumpsumRequestUsesConfigDefaultWhenMissing(): void
    {
        $inputs = InvestmentInputs::fromLumpsumRequest([], $this->configService);
        $defaults = $this->configService->getCalculatorDefaults();
        $expectedDefault = (float) ($defaults['lumpsum']['default'] ?? 500000.0);

        $this->assertSame($expectedDefault, $inputs->getLumpsum());
    }
}
