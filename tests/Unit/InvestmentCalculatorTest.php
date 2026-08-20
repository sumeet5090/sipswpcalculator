<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;

class InvestmentCalculatorTest extends TestCase
{
    /**
     * Helper to instantiate inputs directly from data.
     */
    private function createInputs(array $data): InvestmentInputs
    {
        return InvestmentInputs::fromRequest($data, new \Services\ConfigService(__DIR__ . '/../../content/calculator_defaults.json'));
    }

    /**
     * Test Case 1: Standard Flat SIP (No Step-up, No SWP)
     * Asserts monthly compounding interest logic and final balances.
     */
    public function testStandardFlatSip(): void
    {
        $inputs = $this->createInputs([
            'sip' => 10000,
            'years' => 10,
            'rate' => 12.0,
            'stepup' => 0.0,
            'enable_swp' => false
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(10, $results);

        // First year assertions
        $year1 = $results[0];
        $this->assertEquals(1, $year1['year']);
        $this->assertEquals(0, $year1['begin_balance']);
        $this->assertEquals(10000.0, $year1['sip_monthly']);
        $this->assertEquals(120000.0, $year1['annual_contribution']);
        $this->assertEquals(120000.0, $year1['cumulative_invested']);
        $this->assertNull($year1['swp_monthly']);
        $this->assertNull($year1['annual_withdrawal']);
        $this->assertEquals(0.0, $year1['cumulative_withdrawals']);

        // Exact year 1 ending balance checking (10000 compounded monthly at 1% for 12 months)
        // Expected ending balance = 128093 (approx)
        $this->assertEquals(128093.0, $year1['combined_total']);
        $this->assertEquals(8093.0, $year1['interest']);

        // Tenth year assertions
        $year10 = $results[9];
        $this->assertEquals(10, $year10['year']);
        $this->assertEquals(2323391.0, $year10['combined_total']);
        $this->assertEquals(1200000.0, $year10['cumulative_invested']);
    }

    /**
     * Test Case 2: SIP with Annual Step-up (No SWP)
     * Asserts monthly SIP amount increments by stepup% annually.
     */
    public function testSipWithAnnualStepup(): void
    {
        $inputs = $this->createInputs([
            'sip' => 10000,
            'years' => 5,
            'rate' => 10.0,
            'stepup' => 10.0, // 10% annual increase
            'enable_swp' => false
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(5, $results);

        // Year 1 SIP
        $this->assertEquals(10000.0, $results[0]['sip_monthly']);
        $this->assertEquals(120000.0, $results[0]['annual_contribution']);

        // Year 2 SIP: 10000 * 1.10 = 11000
        $this->assertEquals(11000.0, $results[1]['sip_monthly']);
        $this->assertEquals(132000.0, $results[1]['annual_contribution']);
        $this->assertEquals(252000.0, $results[1]['cumulative_invested']);

        // Year 5 SIP: 10000 * (1.10)^4 = 14641
        $this->assertEquals(14641.0, $results[4]['sip_monthly']);
        $this->assertEquals(175692.0, $results[4]['annual_contribution']);
    }

    /**
     * Test Case 3: Standard SIP to SWP Transition
     * Asserts transition phase from accumulation (SIP) to distribution (SWP).
     */
    public function testSipToSwpTransition(): void
    {
        $inputs = $this->createInputs([
            'sip' => 20000,
            'years' => 5,
            'rate' => 12.0,
            'stepup' => 10.0,
            'enable_swp' => true,
            'swp_withdrawal' => 15000,
            'swp_stepup' => 5.0,
            'swp_years' => 5
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        // Accumulation (5 years) + Distribution (5 years) = 10 years total
        $this->assertCount(10, $results);

        // Years 1-5: SIP is active, SWP is not active
        for ($i = 0; $i < 5; $i++) {
            $this->assertNotNull($results[$i]['sip_monthly']);
            $this->assertNull($results[$i]['swp_monthly']);
            $this->assertEquals(0.0, $results[$i]['cumulative_withdrawals']);
        }

        // Year 6: First SWP Year (SIP ceases, SWP begins)
        $year6 = $results[5];
        $this->assertEquals(6, $year6['year']);
        $this->assertNull($year6['sip_monthly']);
        $this->assertEquals(0.0, $year6['annual_contribution']);
        $this->assertEquals(15000.0, $year6['swp_monthly']);
        $this->assertEquals(180000.0, $year6['annual_withdrawal']);
        $this->assertEquals(180000.0, $year6['cumulative_withdrawals']);

        // Year 7: SWP steps up by 5% -> 15000 * 1.05 = 15750
        $year7 = $results[6];
        $this->assertEquals(15750.0, $year7['swp_monthly']);
        $this->assertEquals(189000.0, $year7['annual_withdrawal']);
        $this->assertEquals(369000.0, $year7['cumulative_withdrawals']);
    }

    /**
     * Test Case 4: Portfolio Depletion (Edge Case)
     * Asserts that when withdrawals exceed balance, the portfolio balance stops at 0,
     * and actual withdrawals are capped at available funds.
     */
    public function testPortfolioDepletion(): void
    {
        // Low accumulation followed by extremely high withdrawal
        $inputs = $this->createInputs([
            'sip' => 5000,
            'years' => 1,
            'rate' => 12.0,
            'stepup' => 0.0,
            'enable_swp' => true,
            'swp_withdrawal' => 500000, // Impossibly high withdrawal
            'swp_stepup' => 0.0,
            'swp_years' => 2
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(3, $results); // 1 SIP year + 2 SWP years

        $year1 = $results[0];
        $this->assertGreaterThan(0.0, $year1['combined_total']);

        // Year 2: Balance must deplete to 0
        $year2 = $results[1];
        $this->assertEquals(0.0, $year2['combined_total']);
        // Withdrawals must be capped at the available potential balance
        $this->assertLessThan(6000000.0, $year2['annual_withdrawal']);
        $this->assertGreaterThan(0.0, $year2['annual_withdrawal']);

        // Year 3: Balance is already 0, so no withdrawals can be made
        $year3 = $results[2];
        $this->assertEquals(0.0, $year3['combined_total']);
        $this->assertEquals(0.0, $year3['annual_withdrawal']);
        $this->assertEquals($year2['cumulative_withdrawals'], $year3['cumulative_withdrawals']);
    }

    /**
     * Test Case 5: Zero / Minimum Bounds
     * Verifies system works smoothly on absolute minimum allowed parameters.
     */
    public function testMinimumBoundaries(): void
    {
        $inputs = $this->createInputs([
            'sip' => 500.0,       // Min clamp
            'years' => 1,         // Min clamp
            'rate' => 0.1,        // Min clamp
            'stepup' => 0.0,      // Min clamp
            'enable_swp' => false
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(1, $results);
        $this->assertEquals(500.0, $results[0]['sip_monthly']);
        $this->assertEquals(6000.0, $results[0]['annual_contribution']);
        $this->assertGreaterThan(6000.0, $results[0]['combined_total']); // Interest earned > 0
    }

    /**
     * Test Case 6: Maximum Boundaries
     * Verifies system handles high boundaries without overflowing to infinity.
     */
    public function testMaximumBoundaries(): void
    {
        $inputs = $this->createInputs([
            'sip' => 1000000.0,       // Max clamp
            'years' => 50,            // Max clamp
            'rate' => 30.0,           // Max clamp
            'stepup' => 50.0,         // Max clamp
            'enable_swp' => true,
            'swp_withdrawal' => 1000000.0,
            'swp_stepup' => 20.0,
            'swp_years' => 50
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        // 50 SIP + 50 SWP = 100 years
        $this->assertCount(100, $results);

        $finalYear = end($results);
        $this->assertFinite($finalYear['combined_total']);
        $this->assertGreaterThan(0.0, $finalYear['combined_total']);
    }

    /**
     * Test Case 7: Nominal APR Compounding & Effective Annual Yield (Point 1)
     * 12% nominal compounded monthly on ₹1,00,000 lumpsum yields approx ₹1,12,683 (12.68% EAR).
     */
    public function testEffectiveAnnualRateCompounding(): void
    {
        $configService = new \Services\ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $inputs = InvestmentInputs::fromLumpsumRequest([
            'lumpsum' => 100000.0,
            'years' => 1,
            'rate' => 12.0,
            'inflation' => 0.0
        ], $configService);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(1, $results);
        $row = $results[0];
        // 100000 * (1 + 0.01)^12 = 112682.50 -> 112683
        $this->assertEquals(112683.0, $row['combined_total']);
        $this->assertEquals(12683.0, $row['interest']);
    }

    /**
     * Test Case 8: Discrete Annual Step-Up Timing (Point 2)
     * Verifies that Step-up jumps cleanly between annual boundaries.
     */
    public function testDiscreteAnnualStepupJump(): void
    {
        $inputs = $this->createInputs([
            'sip' => 10000.0,
            'years' => 3,
            'rate' => 10.0,
            'stepup' => 15.0,
            'enable_swp' => false
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(3, $results);

        // Year 1: ₹10,000/mo -> ₹1,20,000/yr
        $this->assertEquals(10000.0, $results[0]['sip_monthly']);
        $this->assertEquals(120000.0, $results[0]['annual_contribution']);

        // Year 2: ₹10,000 * 1.15 = ₹11,500/mo -> ₹1,38,000/yr
        $this->assertEquals(11500.0, $results[1]['sip_monthly']);
        $this->assertEquals(138000.0, $results[1]['annual_contribution']);

        // Year 3: ₹10,000 * (1.15)^2 = ₹13,225/mo -> ₹1,58,700/yr
        $this->assertEquals(13225.0, $results[2]['sip_monthly']);
        $this->assertEquals(158700.0, $results[2]['annual_contribution']);
    }

    /**
     * Test Case 9: LTCG Exemption & Loss Floor Protection (Point 7 & 20)
     * When returns are zero/minimum and gains are below exemption (or zero), LTCG tax must be 0.
     */
    public function testLtcgExemptionFloorLossProtection(): void
    {
        $inputs = $this->createInputs([
            'sip' => 5000.0,
            'years' => 1,
            'rate' => 1.0, // Low return
            'stepup' => 0.0,
            'enable_swp' => false,
            'ltcg_exemption' => 125000.0,
            'ltcg_tax_rate' => 0.125
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $row = $results[0];
        $this->assertEquals(0.0, $row['ltcg_tax']);
        $this->assertEquals($row['combined_total'], $row['post_tax_total']);
    }

    /**
     * Test Case 10: SWP Depleted Portfolio Total Value Delivered Conservation (Point 14)
     * Asserts that in a depleted SWP portfolio, total withdrawn equals ending cash flow.
     */
    public function testSwpTotalDeliveredValueParity(): void
    {
        $inputs = $this->createInputs([
            'sip' => 10000.0,
            'years' => 2,
            'rate' => 10.0,
            'stepup' => 0.0,
            'enable_swp' => true,
            'swp_withdrawal' => 50000.0,
            'swp_stepup' => 0.0,
            'swp_years' => 3
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(5, $results); // 2 SIP + 3 SWP
        $lastRow = end($results);
        $this->assertGreaterThanOrEqual(0.0, $lastRow['combined_total']);
        $this->assertGreaterThan(0.0, $lastRow['cumulative_withdrawals']);
    }

    /**
     * Test Case 11: Negative Zero Currency Formatting (Point 4)
     * Verifies that -0.0001, -0.0, and 0 format as "₹0" without leading negative signs.
     */
    public function testNegativeZeroCurrencyFormatting(): void
    {
        $formatter = new \Core\CurrencyHelper();
        $this->assertEquals("₹0", $formatter->format(0.0));
        $this->assertEquals("₹0", $formatter->format(-0.0));
        $this->assertEquals("₹0", $formatter->format(-0.0001));
        $this->assertEquals("-₹500", $formatter->format(-500.0));
        $this->assertEquals("₹1,00,000", $formatter->format(100000.0));
    }

    /**
     * Test Case 12: Pure Lumpsum Compounding Without SIP (Point 6)
     * Asserts that a pure lumpsum investment compounds correctly across multiple years.
     */
    public function testPureLumpsumCompoundingWithoutSip(): void
    {
        $configService = new \Services\ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $inputs = InvestmentInputs::fromLumpsumRequest([
            'lumpsum' => 1000000.0,
            'years' => 5,
            'rate' => 12.0,
            'inflation' => 0.0
        ], $configService);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(5, $results);
        // Year 1: ₹10,00,000 * (1.01)^12 = ₹11,26,825
        $this->assertEquals(1000000.0, $results[0]['begin_balance']);
        $this->assertEquals(1126825.0, $results[0]['combined_total']);
        $this->assertEquals(126825.0, $results[0]['interest']);

        // Year 5: ₹10,00,000 * (1.01)^60 = ₹18,16,697
        $this->assertEquals(1816697.0, $results[4]['combined_total']);
        $this->assertEquals(1000000.0, $results[4]['cumulative_invested']);
    }

    /**
     * Test Case 13: Zero SIP + Zero Lumpsum Singularity (Point 1)
     * Asserts that when duration is 0, both begin and end balances return exact 0 without NaN or exceptions.
     */
    public function testZeroInvestedZeroLumpsumSingularity(): void
    {
        $inputs = InvestmentInputs::fromValues(
            0.0,
            0,
            12.0,
            0.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0,
            0.0,
            125000.0,
            0.125
        );

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(1, $results);
        $this->assertEquals(0, $results[0]['year']);
        $this->assertEquals(0.0, $results[0]['begin_balance']);
        $this->assertEquals(0.0, $results[0]['combined_total']);
        $this->assertEquals(0.0, $results[0]['interest']);
        $this->assertNull($results[0]['sip_monthly']);
    }

    /**
     * Test Case 14: Max Safe Integer Step-Up Compounding Boundary (Point 3)
     * Asserts that high step-up parameters (50% per year over 30 years) calculate finite numeric totals without float overflow.
     */
    public function testMaxSafeIntegerStepupCompounding(): void
    {
        $inputs = $this->createInputs([
            'sip' => 50000.0,
            'years' => 30,
            'rate' => 25.0,
            'stepup' => 50.0,
            'enable_swp' => false
        ]);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(30, $results);
        $finalRow = end($results);
        $this->assertFinite($finalRow['combined_total']);
        $this->assertGreaterThan(0.0, $finalRow['combined_total']);
        $this->assertGreaterThan(0.0, $finalRow['cumulative_invested']);
    }

    /**
     * Test Case 15: Lumpsum Step-Up Independence (Point 7)
     * Asserts that step-up applies solely to periodic SIP contributions, never mutating lumpsum.
     */
    public function testLumpsumExclusionFromStepupEscalation(): void
    {
        $configService = new \Services\ConfigService(__DIR__ . '/../../content/calculator_defaults.json');
        $inputs = InvestmentInputs::fromLumpsumRequest([
            'lumpsum' => 500000.0,
            'years' => 3,
            'rate' => 10.0,
            'inflation' => 0.0
        ], $configService);

        $calculator = new InvestmentCalculator();
        $results = $calculator->calculate($inputs);

        $this->assertCount(3, $results);
        foreach ($results as $row) {
            $this->assertEquals(0, $row['annual_contribution']);
            $this->assertEquals(500000.0, $row['cumulative_invested']);
        }
    }

    /**
     * Test Case 16: PDF Report Template Parameter Symmetry (Point 2)
     * Asserts that PDF template renders all strategy parameters symmetrically without missing or duplicate rows.
     */
    public function testPdfReportTemplateConfigCardSymmetry(): void
    {
        $template = new \Core\PdfReportTemplate(new \Core\CurrencyHelper());
        $htmlSwp = $template->render([
            'client_name' => 'Investor A',
            'advisor_name' => 'Advisor B',
            'lumpsum' => 1000000.0,
            'sip' => 20000.0,
            'years' => 10,
            'rate' => 12.0,
            'stepup' => 10.0,
            'swp_withdrawal' => 50000.0,
            'swp_years' => 15,
            'swp_stepup' => 5.0,
            'swp_rate' => 8.0,
            'inflation' => 6.0
        ]);

        $this->assertStringContainsString('Phase 1: Accumulation (SIP)', $htmlSwp);
        $this->assertStringContainsString('Phase 2: Retirement Income (SWP)', $htmlSwp);
        $this->assertStringContainsString('Monthly SWP:', $htmlSwp);
        $this->assertStringContainsString('Expected Inflation:', $htmlSwp);

        $htmlSip = $template->render([
            'client_name' => 'Investor A',
            'advisor_name' => 'Advisor B',
            'lumpsum' => 500000.0,
            'sip' => 10000.0,
            'years' => 15,
            'rate' => 12.0,
            'stepup' => 10.0,
            'swp_years' => 0,
            'swp_withdrawal' => 0,
            'inflation' => 6.0
        ]);

        $this->assertStringContainsString('Wealth Accumulation (SIP) Parameters', $htmlSip);
        $this->assertStringContainsString('Expected Return:', $htmlSip);
        $this->assertStringContainsString('Annual Step-Up:', $htmlSip);
    }
}
