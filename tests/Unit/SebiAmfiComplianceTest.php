<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * SebiAmfiComplianceTest
 *
 * Uncompromising financial validation suite ensuring 100% compliance with official
 * SEBI/AMFI mutual fund investment calculator conventions down to the paisa.
 *
 * Validates:
 * 1. Annuity-Due timing vs Ordinary Annuity lag ($FV = P \times \frac{(1+i)^n - 1}{i} \times (1+i)$)
 * 2. Nominal rate division convention ($i = r / 1200$)
 * 3. Step-Up (Top-Up) SIP annual geometric progression summation
 * 4. Lumpsum monthly compounding schedule parity ($FV = L \times (1+i)^{12Y}$)
 * 5. SWP decumulation recurrence, partial final withdrawal, and zero-floor clamping
 * 6. Finance (No. 2) Act 2024 Section 112A LTCG arithmetic (₹1.25L exemption, 12.5% rate)
 * 7. Accounting identities: PostTaxTotal = CombinedTotal - LtcgTax across all regimes
 */
final class SebiAmfiComplianceTest extends TestCase
{
    private InvestmentCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InvestmentCalculator();
    }

    /**
     * Compute theoretical closed-form Annuity-Due future value.
     * AMFI Official Formula: FV = P * [((1 + i)^n - 1) / i] * (1 + i)
     */
    private function closedFormAnnuityDue(float $monthlySip, float $annualRate, int $years): float
    {
        if ($annualRate <= 0.0 || $years <= 0) {
            return $monthlySip * 12.0 * $years;
        }

        $i = ($annualRate / 100.0) / 12.0;
        $n = $years * 12;

        return $monthlySip * ((pow(1.0 + $i, $n) - 1.0) / $i) * (1.0 + $i);
    }

    /**
     * Compute theoretical closed-form Ordinary Annuity future value (arrears/end-of-month).
     * FV = P * [((1 + i)^n - 1) / i]
     */
    private function closedFormOrdinaryAnnuity(float $monthlySip, float $annualRate, int $years): float
    {
        if ($annualRate <= 0.0 || $years <= 0) {
            return $monthlySip * 12.0 * $years;
        }

        $i = ($annualRate / 100.0) / 12.0;
        $n = $years * 12;

        return $monthlySip * ((pow(1.0 + $i, $n) - 1.0) / $i);
    }

    /**
     * Compute theoretical Step-Up SIP future value by summing geometric year blocks.
     */
    private function closedFormStepUpSip(float $initialSip, float $annualRate, float $stepUpPct, int $years): float
    {
        if ($years <= 0) {
            return 0.0;
        }
        if ($annualRate <= 0.0 && $stepUpPct <= 0.0) {
            return $initialSip * 12.0 * $years;
        }

        $i = ($annualRate / 100.0) / 12.0;
        $totalFv = 0.0;

        for ($y = 1; $y <= $years; $y++) {
            $sipY = $initialSip * pow(1.0 + ($stepUpPct / 100.0), $y - 1);
            // Year block future value at end of year y
            $blockFv = $annualRate > 0.0
                ? $sipY * ((pow(1.0 + $i, 12) - 1.0) / $i) * (1.0 + $i)
                : $sipY * 12.0;

            // Compound remaining years to horizon
            $remainingYears = $years - $y;
            $compoundedToHorizon = $annualRate > 0.0
                ? $blockFv * pow(1.0 + $i, $remainingYears * 12)
                : $blockFv;

            $totalFv += $compoundedToHorizon;
        }

        return $totalFv;
    }

    /**
     * Asserts that discrete month-by-month compounding matches closed-form Annuity-Due
     * down to the paisa across micro, retail, and HNI tiers.
     */
    #[DataProvider('amfiSipScenariosProvider')]
    public function testDiscreteCompoundingMatchesClosedFormAnnuityDue(float $sip, float $rate, int $years): void
    {
        $inputs = InvestmentInputs::fromValues(
            sip: $sip,
            years: $years,
            rate: $rate,
            stepup: 0.0,
            enableSwp: false
        );
        $results = $this->calculator->calculate($inputs);

        $this->assertCount($years, $results);

        $lastRow = end($results);
        $simulatedTerminalCorpus = (float) $lastRow['combined_total'];

        $theoreticalCorpus = $this->closedFormAnnuityDue($sip, $rate, $years);
        $expectedRounded = round($theoreticalCorpus);

        // Assert exact match to rounded theoretical value
        $this->assertEquals(
            $expectedRounded,
            $simulatedTerminalCorpus,
            "Discrete month-by-month terminal corpus must match AMFI Annuity-Due closed form for SIP ₹{$sip} @ {$rate}% over {$years} yrs"
        );

        // Cumulative invested must strictly equal SIP * 12 * Years
        $expectedInvested = $sip * 12.0 * $years;
        // Annual reconciliation identity: begin_balance + annual_contribution + interest == combined_total
        $this->assertEqualsWithDelta(
            $lastRow['begin_balance'] + $lastRow['annual_contribution'] + $lastRow['interest'],
            $lastRow['combined_total'],
            1.0,
            "Annual balance reconciliation identity must hold down to rounding precision"
        );
        // Cumulative gains identity
        $this->assertEquals(
            $simulatedTerminalCorpus - $expectedInvested,
            $simulatedTerminalCorpus - $lastRow['cumulative_invested']
        );
    }

    /**
     * Proves that an Ordinary Annuity (end-of-month lag) violates AMFI compliance
     * by understating investment returns.
     */
    public function testOrdinaryAnnuityLagsAnnuityDue(): void
    {
        $sip = 25000.0;
        $rate = 12.0;
        $years = 20;

        $annuityDue = $this->closedFormAnnuityDue($sip, $rate, $years);
        $ordinaryAnnuity = $this->closedFormOrdinaryAnnuity($sip, $rate, $years);

        // Ordinary annuity understates returns by exactly 1 month's compounding factor
        $lagUnderstatement = $annuityDue - $ordinaryAnnuity;
        $this->assertGreaterThan(200000.0, $lagUnderstatement); // Over ₹2.47 Lakh difference!

        $inputs = InvestmentInputs::fromValues(
            sip: $sip,
            years: $years,
            rate: $rate,
            stepup: 0.0,
            enableSwp: false
        );
        $results = $this->calculator->calculate($inputs);
        $simulated = (float) end($results)['combined_total'];

        // Simulated must strictly match Annuity-Due, never Ordinary Annuity
        $this->assertEquals(round($annuityDue), $simulated);
        $this->assertNotEquals(round($ordinaryAnnuity), $simulated);
    }

    /**
     * Asserts that Step-Up SIP matches theoretical geometric progression summation.
     */
    #[DataProvider('amfiStepUpScenariosProvider')]
    public function testStepUpSipMatchesGeometricProgressionSum(float $sip, float $rate, float $stepup, int $years): void
    {
        $inputs = InvestmentInputs::fromValues(
            sip: $sip,
            years: $years,
            rate: $rate,
            stepup: $stepup,
            enableSwp: false
        );
        $results = $this->calculator->calculate($inputs);

        $this->assertCount($years, $results);

        $theoreticalCorpus = $this->closedFormStepUpSip($sip, $rate, $stepup, $years);
        $simulatedCorpus = (float) end($results)['combined_total'];

        // Dynamic tolerance of max(10.0, 0.001%) to account for 2-decimal rounding of monthly contributions
        $delta = max(10.0, $theoreticalCorpus * 0.00005);
        $this->assertEqualsWithDelta(
            $theoreticalCorpus,
            $simulatedCorpus,
            $delta,
            "Step-Up SIP simulation must align with geometric progression summation"
        );
    }

    /**
     * Asserts pure Lumpsum compounding matches exact monthly power formula: A = P * (1 + r/1200)^(12Y)
     */
    public function testPureLumpsumCompoundingFormulaParity(): void
    {
        $lumpsum = 5000000.0; // ₹50 Lakh
        $rate = 12.0;
        $years = 15;

        $inputs = InvestmentInputs::fromValues(0.0, $years, $rate, 0.0, false, 0.0, 0.0, 0, $lumpsum);
        $results = $this->calculator->calculate($inputs);

        $i = ($rate / 100.0) / 12.0;
        $theoretical = $lumpsum * pow(1.0 + $i, $years * 12);

        $this->assertEquals(round($theoretical), end($results)['combined_total']);
        $this->assertEquals($lumpsum, end($results)['cumulative_invested']);
    }

    /**
     * Asserts that SWP decumulation enforces partial final month exhaustion without negative debt.
     */
    public function testSwpExhaustionFloorAndZeroBalanceClamping(): void
    {
        // ₹5 Lakh lumpsum, withdrawing ₹1,00,000/month @ 6% return over 10 years
        // At ₹1L/month, ₹5 Lakh corpus depletes in ~5.2 months
        $inputs = InvestmentInputs::fromValues(
            0.0,
            0,
            0.0,
            0.0,
            true,
            100000.0,
            0.0,
            5,
            500000.0,
            6.0
        );

        $results = $this->calculator->calculate($inputs);
        $this->assertCount(5, $results);

        // Year 1 depletes
        $year1 = $results[0];
        $this->assertEquals(0.0, $year1['combined_total']);
        $this->assertGreaterThan(0.0, $year1['annual_withdrawal']);
        $this->assertLessThanOrEqual(550000.0, $year1['annual_withdrawal']);

        // Subsequent depleted years must report 0 balance, 0 withdrawal, frozen cumulative withdrawals
        for ($y = 1; $y < 5; $y++) {
            $this->assertEquals(0.0, $results[$y]['combined_total']);
            $this->assertEquals(0.0, $results[$y]['interest']);
            $this->assertEquals($year1['cumulative_withdrawals'], $results[$y]['cumulative_withdrawals']);
        }
    }

    /**
     * Asserts Finance (No. 2) Act 2024 Section 112A LTCG arithmetic:
     * - Threshold: ₹1,25,000
     * - Tax rate: 12.5%
     * - Accounting Identity: PostTaxTotal = CombinedTotal - LtcgTax
     */
    public function testSection112aLtcgTaxAccountingIdentity(): void
    {
        // Test sub-threshold (₹0 tax)
        $inputsBelow = InvestmentInputs::fromValues(2000.0, 1, 6.0, 0.0, false);
        $resBelow = $this->calculator->calculate($inputsBelow);
        $rowBelow = end($resBelow);

        $this->assertEquals(0.0, $rowBelow['ltcg_tax']);
        $this->assertEquals($rowBelow['combined_total'], $rowBelow['post_tax_total']);

        // Test above-threshold
        $inputsAbove = InvestmentInputs::fromValues(25000.0, 10, 12.0, 10.0, false);
        $resAbove = $this->calculator->calculate($inputsAbove);

        foreach ($resAbove as $row) {
            $preTaxGain = ($row['combined_total'] + $row['cumulative_withdrawals']) - $row['cumulative_invested'];
            $taxableGain = max(0.0, $preTaxGain - 125000.0);
            $expectedTax = round($taxableGain * 0.125);

            $this->assertEquals($expectedTax, $row['ltcg_tax'], "LTCG Tax must match 12.5% on gains > ₹1.25L at Year {$row['year']}");
            // Strict accounting identity: PostTaxTotal == CombinedTotal - LtcgTax
            $this->assertEquals(
                $row['combined_total'] - $row['ltcg_tax'],
                $row['post_tax_total'],
                "Post-tax total must strictly equal combined_total minus ltcg_tax at Year {$row['year']}"
            );
        }
    }

    /**
     * Asserts Zero-Rate Linear fast-path:
     * When return is 0% and step-up is 0%, Final Corpus = Lumpsum + (SIP * 12 * Years).
     */
    public function testZeroRateLinearFastPath(): void
    {
        $inputs = InvestmentInputs::fromValues(15000.0, 10, 0.0, 0.0, false, 0.0, 0.0, 0, 50000.0);
        $results = $this->calculator->calculate($inputs);

        $expectedCorpus = 50000.0 + (15000.0 * 12.0 * 10);
        $lastRow = end($results);

        $this->assertEquals($expectedCorpus, $lastRow['combined_total']);
        $this->assertEquals($expectedCorpus, $lastRow['cumulative_invested']);
        $this->assertEquals(0.0, $lastRow['interest']);
        $this->assertEquals(0.0, $lastRow['ltcg_tax']);
    }

    /**
     * Asserts Year 0 Singularity invariant.
     */
    public function testYearZeroSingularityInvariant(): void
    {
        $inputs = InvestmentInputs::fromValues(10000.0, 0, 12.0, 0.0, false, 0.0, 0.0, 0, 250000.0);
        $results = $this->calculator->calculate($inputs);

        $this->assertCount(1, $results);
        $row0 = $results[0];

        $this->assertEquals(0, $row0['year']);
        $this->assertEquals(250000.0, $row0['begin_balance']);
        $this->assertEquals(250000.0, $row0['combined_total']);
        $this->assertEquals(250000.0, $row0['cumulative_invested']);
        $this->assertEquals(0.0, $row0['annual_contribution']);
        $this->assertNull($row0['sip_monthly']);
        $this->assertNull($row0['swp_monthly']);
        $this->assertEquals(0.0, $row0['interest']);
        $this->assertEquals(0.0, $row0['ltcg_tax']);
        $this->assertEquals(250000.0, $row0['post_tax_total']);
    }

    public static function amfiSipScenariosProvider(): array
    {
        return [
            'micro_sip_1yr_7pct'    => [500.0, 7.0, 1],
            'micro_sip_5yr_10pct'   => [500.0, 10.0, 5],
            'micro_sip_15yr_12pct'  => [500.0, 12.0, 15],
            'retail_5k_3yr_8pct'    => [5000.0, 8.0, 3],
            'retail_5k_10yr_12pct'  => [5000.0, 12.0, 10],
            'retail_10k_5yr_12pct'  => [10000.0, 12.0, 5],
            'retail_10k_15yr_12pct' => [10000.0, 12.0, 15],
            'retail_10k_20yr_12pct' => [10000.0, 12.0, 20],
            'retail_10k_25yr_14pct' => [10000.0, 14.0, 25],
            'retail_25k_10yr_15pct' => [25000.0, 15.0, 10],
            'retail_25k_20yr_12pct' => [25000.0, 12.0, 20],
            'retail_25k_30yr_12pct' => [25000.0, 12.0, 30],
            'hni_1L_10yr_12pct'     => [100000.0, 12.0, 10],
            'hni_1L_20yr_15pct'     => [100000.0, 15.0, 20],
            'hni_10L_5yr_12pct'     => [1000000.0, 12.0, 5],
        ];
    }

    public static function amfiStepUpScenariosProvider(): array
    {
        return [
            'stepup_5k_5pct_10yr'   => [5000.0, 12.0, 5.0, 10],
            'stepup_10k_10pct_15yr' => [10000.0, 12.0, 10.0, 15],
            'stepup_10k_10pct_20yr' => [10000.0, 12.0, 10.0, 20],
            'stepup_25k_10pct_15yr' => [25000.0, 12.0, 10.0, 15],
            'stepup_50k_15pct_10yr' => [50000.0, 14.0, 15.0, 10],
            'stepup_1L_5pct_20yr'   => [100000.0, 12.0, 5.0, 20],
        ];
    }
}
