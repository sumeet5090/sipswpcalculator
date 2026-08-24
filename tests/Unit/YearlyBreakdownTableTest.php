<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;

/**
 * Validates Yearly Breakdown Table (Panel 1) template architecture,
 * DOM IDs, column density switches, compounding ignition milestones,
 * and wealth multiplier calculations.
 */
final class YearlyBreakdownTableTest extends TestCase
{
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InvestmentCalculator();
    }

    public function testYearlyBreakdownTemplateContainsRequiredDomElements(): void
    {
        $templatePath = __DIR__ . '/../../src/Views/components/yearly-breakdown-table.twig';
        $this->assertFileExists($templatePath);
        $content = (string) file_get_contents($templatePath);

        // Assert operational dock controls
        $this->assertStringContainsString('id="yearly-breakdown"', $content);
        $this->assertStringContainsString('id="table-density-5y"', $content);
        $this->assertStringContainsString('id="table-density-all"', $content);
        $this->assertStringContainsString('id="table-col-essential"', $content);
        $this->assertStringContainsString('id="table-col-audit"', $content);

        // Assert dispatch dock buttons
        $this->assertStringContainsString('id="downloadCsvBtn"', $content);
        $this->assertStringContainsString('id="openPdfModalBtn"', $content);
        $this->assertStringContainsString('id="shareCalcBtn"', $content);
        $this->assertStringContainsString('id="customizePdfModalBtn"', $content);

        // Assert table container and sticky header
        $this->assertStringContainsString('id="results-table"', $content);
        $this->assertStringContainsString('id="breakdown-head"', $content);
        $this->assertStringContainsString('id="breakdown-body"', $content);
        $this->assertStringContainsString('sticky left-0', $content);

        // Assert mobile cards and progressive disclosure
        $this->assertStringContainsString('id="mobile-breakdown-cards"', $content);
        $this->assertStringContainsString('id="mobile-expand-all-years-btn"', $content);
    }

    public function testCompoundingIgnitionYearDetectionParity(): void
    {
        // SIP ₹10k/mo, 15 years @ 12% p.a., 0% step-up (Annual contribution = ₹1,20,000)
        $inputs = InvestmentInputs::fromValues(
            10000.0,
            15,
            12.0,
            0.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $results = $this->calculator->calculate($inputs);
        $ignitionYear = null;

        foreach ($results as $row) {
            if ($row['year'] > 1 && ($row['interest'] ?? 0.0) >= ($row['annual_contribution'] ?? 120000.0)) {
                $ignitionYear = $row['year'];
                break;
            }
        }

        // At 12% p.a., annual interest reaches/surpasses ₹1,20,000 by Year 7
        $this->assertNotNull($ignitionYear);
        $this->assertSame(7, $ignitionYear, 'Annual gains overtake annual contribution in Year 7');
    }

    public function testWealthMultiplierCalculation(): void
    {
        // SIP ₹20k/mo, 20 years @ 12% p.a., 10% annual step-up
        $inputs = InvestmentInputs::fromValues(
            20000.0,
            20,
            12.0,
            10.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $results = $this->calculator->calculate($inputs);
        $finalRow = end($results);

        $invested = $finalRow['cumulative_invested'];
        $corpus = $finalRow['combined_total'];
        $multiplier = round($corpus / $invested, 1);

        $this->assertGreaterThanOrEqual(2.5, $multiplier);
        $this->assertLessThanOrEqual(4.5, $multiplier);
    }

    public function testPureLightModeComplianceInTable(): void
    {
        $templatePath = __DIR__ . '/../../src/Views/components/yearly-breakdown-table.twig';
        $content = (string) file_get_contents($templatePath);

        // Strict pure light mode: no dark container backgrounds
        $this->assertStringNotContainsString('bg-slate-900', $content);
        $this->assertStringNotContainsString('bg-slate-950', $content);
        $this->assertStringNotContainsString('bg-gray-900', $content);
        $this->assertStringContainsString('bg-white/95', $content);
        $this->assertStringContainsString('border-slate-200', $content);
    }
}
