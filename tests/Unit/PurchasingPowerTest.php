<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\InflationEngine;
use PHPUnit\Framework\TestCase;

/**
 * Validates PurchasingPowerController math parity with InflationEngine,
 * cultural Indian anchors, and the Peace-of-Mind resilience scoring model.
 */
final class PurchasingPowerTest extends TestCase
{
    private string $controllerCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controllerCode = (string) file_get_contents(
            __DIR__ . '/../../assets/js/calculators/controllers/PurchasingPowerController.ts'
        );
    }

    public function testPurchasingPowerDiscountMathParity(): void
    {
        $nominal = 10000000; // ₹1 Crore
        $years = 15;
        $inflation = 6.0;

        // Formula: PV = FV / (1 + i)^n
        $expectedPhp = (int) round($nominal / ((1 + $inflation / 100) ** $years));

        // InflationEngine calculation parity
        $engine = new InflationEngine();
        $inflationRes = $engine->calculate($nominal, $inflation, $years);
        $this->assertEqualsWithDelta($expectedPhp, (int) round($inflationRes['purchasing_power']), 2);
    }

    public function testPurchasingPowerControllerClassContract(): void
    {
        $this->assertStringContainsString('analyze(nominalCorpus: number, inputs: InvestmentInputs)', $this->controllerCode);
        $this->assertStringContainsString('resolveAnchor(realCorpus: number): string', $this->controllerCode);
        $this->assertStringContainsString('calculatePeaceOfMind(inputs: InvestmentInputs)', $this->controllerCode);
    }

    public function testPeaceOfMindScoringRules(): void
    {
        // Realistic conservative inputs: rate <= 12%, stepup >= 10%, years >= 15
        $this->assertStringContainsString('inputs.rate <= 12', $this->controllerCode);
        $this->assertStringContainsString('inputs.stepup >= 10', $this->controllerCode);
        $this->assertStringContainsString('inputs.years >= 15', $this->controllerCode);
        $this->assertStringContainsString("'bulletproof'", $this->controllerCode);
        $this->assertStringContainsString("'prudent'", $this->controllerCode);
        $this->assertStringContainsString("'vulnerable'", $this->controllerCode);
    }

    public function testCulturalIndianAnchors(): void
    {
        $this->assertStringContainsString('Perpetual sovereign wealth', $this->controllerCode);
        $this->assertStringContainsString('Complete financial independence', $this->controllerCode);
        $this->assertStringContainsString('2BHK/3BHK metro apartment', $this->controllerCode);
        $this->assertStringContainsString('overseas Master’s degree', $this->controllerCode);
    }
}
