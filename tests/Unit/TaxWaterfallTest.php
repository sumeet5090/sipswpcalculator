<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;

final class TaxWaterfallTest extends TestCase
{
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InvestmentCalculator();
    }

    public function testSection112aTaxWaterfallCalculation(): void
    {
        // 10-year SIP of ₹25,000/mo @ 12% return with 10% step-up
        $inputs = InvestmentInputs::fromValues(
            25000.0,
            10,
            12.0,
            10.0,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0,
            6.0,
            125000.0,
            0.125
        );

        $results = $this->calculator->calculate($inputs);
        $this->assertCount(10, $results);

        $lastRow = end($results);
        $invested = $lastRow['cumulative_invested'];
        $grossCorpus = $lastRow['combined_total'];
        $totalGains = $grossCorpus - $invested;

        $this->assertGreaterThan(4000000.0, $invested);
        $this->assertGreaterThan(7000000.0, $grossCorpus);

        // Standard liquidation at maturity: 12.5% on gains exceeding single ₹1.25L threshold
        $standardExemption = 125000.0;
        $taxableGains = max(0.0, $totalGains - $standardExemption);
        $expectedTax = round($taxableGains * 0.125);

        $this->assertEquals($expectedTax, $lastRow['ltcg_tax']);
        $this->assertEquals($grossCorpus - $expectedTax, $lastRow['post_tax_total']);
    }

    public function testTaxHarvestingSavingsCalculation(): void
    {
        $inputs = InvestmentInputs::fromValues(
            20000.0,
            15,
            12.0,
            10.0,
            false,
            0.0,
            0.0,
            0,
            0.0
        );

        $results = $this->calculator->calculate($inputs);
        $savings = $this->calculator->calculateTaxHarvestingSavings($inputs, $results);

        $this->assertGreaterThan(0.0, $savings['standardTax']);
        $this->assertGreaterThan(0.0, $savings['cumulativeSavings']);
        $this->assertLessThanOrEqual($savings['standardTax'], $savings['cumulativeSavings']);
    }
}
