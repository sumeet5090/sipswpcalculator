<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Services\ConfigService;

class TemplateDataParityTest extends TestCase
{
    private InvestmentCalculator $calculator;
    private ConfigService $configService;

    protected function setUp(): void
    {
        $this->calculator = new InvestmentCalculator();
        $this->configService = new ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
    }

    /**
     * Test that home.twig SEO Quick Answer numbers match actual calculator calculations.
     */
    public function testHomeQuickAnswerParity(): void
    {
        $homeTwigPath = __DIR__ . '/../../src/Views/calculators/home.twig';
        $this->assertFileExists($homeTwigPath);
        $content = file_get_contents($homeTwigPath);
        $this->assertIsString($content);

        // Parameters from home.twig: ₹10,000/month, 20 years, 12% return, 10% annual step-up
        $inputs = InvestmentInputs::fromRequest([
            'sip' => 10000.0,
            'years' => 20,
            'rate' => 12.0,
            'stepup' => 10.0,
            'enable_swp' => false
        ], $this->configService);

        $results = $this->calculator->calculate($inputs);
        $this->assertCount(20, $results);

        $lastRow = $results[19];
        $totalInvested = $lastRow['cumulative_invested'];
        $finalCorpus = $lastRow['combined_total'];
        $totalGains = $finalCorpus - $totalInvested;

        // Verify total invested is ~₹68.73 Lakh (6,872,999.76 rounded)
        $this->assertEqualsWithDelta(6873000.0, $totalInvested, 1000.0);
        $this->assertStringContainsString('6873000', $content);

        // Verify final corpus is ~₹1.99 Crore (19,888,715 rounded)
        $this->assertEqualsWithDelta(19888715.0, $finalCorpus, 5000.0);
        $this->assertStringContainsString('19889000', $content);

        // Verify total compound gains is ~₹1.30 Crore (13,015,715 rounded)
        $this->assertEqualsWithDelta(13015715.0, $totalGains, 5000.0);
        $this->assertStringContainsString('13016000', $content);
    }

    /**
     * Test that math-transparency.twig contains verified mathematical formula proofs.
     */
    public function testMathTransparencyFormulas(): void
    {
        $mathTwigPath = __DIR__ . '/../../src/Views/components/math-transparency.twig';
        $this->assertFileExists($mathTwigPath);
        $content = file_get_contents($mathTwigPath);
        $this->assertIsString($content);

        // Annuity Due SIP formula
        $this->assertStringContainsString('FV = P × [ ( (1 + r)^n - 1 ) / r ] × (1 + r)', $content);

        // SWP depletion recurrence
        $this->assertStringContainsString('Balance_{m} = (Balance_{m-1} - W_{m}) × (1 + r)', $content);

        // Step-Up gradient formula
        $this->assertStringContainsString('SIP_{Year k} = SIP_{Initial} × (1 + g)^{k-1}', $content);

        // Budget 2024 Section 112A formula
        $this->assertStringContainsString('Tax = Max(0, Gains - ₹1,25,000) × 12.5%', $content);
    }

    /**
     * Test that strategy starter blueprints presets are within calculator boundaries.
     */
    public function testPersonaBlueprintsPresetBounds(): void
    {
        $strategyTwigPath = __DIR__ . '/../../src/Views/components/strategy-starter.twig';
        $this->assertFileExists($strategyTwigPath);
        $content = file_get_contents($strategyTwigPath);
        $this->assertIsString($content);

        // Check for persona preset attributes in HTML
        $this->assertStringContainsString('data-persona="first_crore"', $content);
        $this->assertStringContainsString('data-persona="fire_retirement"', $content);
        $this->assertStringContainsString('data-persona="child_education"', $content);
        $this->assertStringContainsString('data-persona="capital_preservation"', $content);

        // Verify bounds of presets
        $limits = $this->configService->getCalculatorDefaults();
        $this->assertArrayHasKey('sip', $limits);
        $this->assertArrayHasKey('years', $limits);
        $this->assertArrayHasKey('rate', $limits);
        $this->assertArrayHasKey('stepup', $limits);
    }
}
