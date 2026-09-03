<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Math\PpfEngine;
use PHPUnit\Framework\TestCase;

class PpfEngineTest extends TestCase
{
    public function testStandard15YearPpfProjectionWithAnnualDeposit(): void
    {
        // ₹1,50,000 yearly deposit at 7.1% for standard 15 years
        $result = PpfEngine::calculate(150000.0, 7.1, 15, 'beginning');

        $this->assertEquals(2250000.0, $result['total_invested']);
        $this->assertEquals(7.1, $result['interest_rate']);
        $this->assertEquals(15, $result['tenure_years']);
        $this->assertCount(15, $result['schedule']);

        // First year: 1,50,000 + 7.1% = 1,50,000 + 10,650 = 1,60,650
        $year1 = $result['schedule'][0];
        $this->assertEquals(1, $year1['year']);
        $this->assertEquals(0.0, $year1['opening_balance']);
        $this->assertEquals(150000.0, $year1['annual_deposit']);
        $this->assertEquals(10650.0, $year1['interest_earned']);
        $this->assertEquals(160650.0, $year1['closing_balance']);

        // Standard PPF 15-year maturity on ₹1.5L/year is approximately ₹40.68 Lakh
        $this->assertGreaterThan(4000000.0, $result['maturity_amount']);
        $this->assertLessThan(4150000.0, $result['maturity_amount']);
        $this->assertEquals($result['total_invested'] + $result['total_interest'], $result['maturity_amount']);
    }

    public function testPpfMonthlyDepositVsBeginningDepositYield(): void
    {
        // Monthly deposit earns slightly less than lump sum deposited before April 5th
        $lumpSum = PpfEngine::calculate(120000.0, 7.1, 15, 'beginning');
        $monthly = PpfEngine::calculate(120000.0, 7.1, 15, 'monthly');

        $this->assertEquals($lumpSum['total_invested'], $monthly['total_invested']);
        $this->assertGreaterThan($monthly['maturity_amount'], $lumpSum['maturity_amount']);
    }

    public function testPpf5YearBlockExtensions(): void
    {
        // 20 years and 25 years extensions
        $ppf15 = PpfEngine::calculate(100000.0, 7.1, 15);
        $ppf20 = PpfEngine::calculate(100000.0, 7.1, 20);
        $ppf25 = PpfEngine::calculate(100000.0, 7.1, 25);

        $this->assertCount(20, $ppf20['schedule']);
        $this->assertCount(25, $ppf25['schedule']);
        $this->assertGreaterThan($ppf15['maturity_amount'], $ppf20['maturity_amount']);
        $this->assertGreaterThan($ppf20['maturity_amount'], $ppf25['maturity_amount']);
    }

    public function testPpfBoundariesClamping(): void
    {
        // Deposit exceeding ₹1.5L cap is clamped to 150000
        $overCap = PpfEngine::calculate(250000.0, 7.1, 15);
        $this->assertEquals(150000.0 * 15, $overCap['total_invested']);

        // Negative deposit clamped to 0
        $zeroDeposit = PpfEngine::calculate(-500.0, 7.1, 15);
        $this->assertEquals(0.0, $zeroDeposit['total_invested']);
        $this->assertEquals(0.0, $zeroDeposit['maturity_amount']);
    }

    public function testStatutoryMinimumDepositFiveHundred(): void
    {
        // Minimum deposit ₹500/year
        $result = PpfEngine::calculate(500.0, 7.1, 15);

        $this->assertEquals(7500.0, $result['total_invested']);
        $this->assertCount(15, $result['schedule']);
        $this->assertGreaterThan(7500.0, $result['maturity_amount']);
        $this->assertEqualsWithDelta($result['total_invested'] + $result['total_interest'], $result['maturity_amount'], 0.05);
    }

    public function testMaximumThirtyFiveYearsExtension(): void
    {
        // 35 years: 15 base + 4 extensions of 5 years
        $result = PpfEngine::calculate(150000.0, 7.1, 35);

        $this->assertEquals(35, $result['tenure_years']);
        $this->assertCount(35, $result['schedule']);
        $this->assertEquals(150000.0 * 35, $result['total_invested']);
        $this->assertGreaterThan(20000000.0, $result['maturity_amount']); // > ₹2 Crore
        $this->assertEqualsWithDelta($result['schedule'][34]['closing_balance'], $result['maturity_amount'], 0.05);
    }

    public function testRateSensitivity(): void
    {
        $res71 = PpfEngine::calculate(100000.0, 7.1, 15);
        $res75 = PpfEngine::calculate(100000.0, 7.5, 15);
        $res80 = PpfEngine::calculate(100000.0, 8.0, 15);
        $res85 = PpfEngine::calculate(100000.0, 8.5, 15);

        $this->assertGreaterThan($res71['maturity_amount'], $res75['maturity_amount']);
        $this->assertGreaterThan($res75['maturity_amount'], $res80['maturity_amount']);
        $this->assertGreaterThan($res80['maturity_amount'], $res85['maturity_amount']);
    }

    public function testLedgerAccountingIdentity(): void
    {
        $result = PpfEngine::calculate(120000.0, 7.1, 20, 'monthly');

        $this->assertCount(20, $result['schedule']);
        $sumInterest = 0.0;

        for ($i = 0; $i < 20; $i++) {
            $row = $result['schedule'][$i];
            $this->assertEquals($i + 1, $row['year']);
            $this->assertEqualsWithDelta($row['opening_balance'] + $row['annual_deposit'] + $row['interest_earned'], $row['closing_balance'], 0.05);

            if ($i > 0) {
                $this->assertEqualsWithDelta($result['schedule'][$i - 1]['closing_balance'], $row['opening_balance'], 0.05);
            }
            $sumInterest += $row['interest_earned'];
        }

        $this->assertEqualsWithDelta($result['total_interest'], $sumInterest, 0.05);
        $this->assertEqualsWithDelta($result['total_invested'] + $result['total_interest'], $result['maturity_amount'], 0.05);
    }
}
