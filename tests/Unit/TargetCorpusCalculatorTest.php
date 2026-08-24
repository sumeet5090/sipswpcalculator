<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\InvestmentCalculator;
use Core\InvestmentInputs;
use Core\Strategies\StrategyFactory;
use Core\Strategies\TargetCorpusStrategy;
use Services\ConfigServiceInterface;

final class TargetCorpusCalculatorTest extends TestCase
{
    private InvestmentCalculator $calculator;
    private ConfigServiceInterface $configService;

    protected function setUp(): void
    {
        $this->calculator = new InvestmentCalculator();
        $this->configService = $this->createMock(ConfigServiceInterface::class);
        $this->configService->method('getCalculatorDefaults')->willReturn([
            'sip'            => ['default' => 10000, 'min' => 500, 'max' => 1000000, 'slider_max' => 100000, 'step' => 500, 'label' => 'Monthly SIP', 'prefix' => '₹'],
            'years'          => ['default' => 15, 'min' => 1, 'max' => 40, 'slider_max' => 40, 'step' => 1, 'label' => 'Time to Goal', 'suffix' => 'Yrs'],
            'rate'           => ['default' => 12, 'min' => 1, 'max' => 30, 'slider_max' => 20, 'step' => 0.5, 'label' => 'Expected Return', 'suffix' => '%'],
            'stepup'         => ['default' => 10, 'min' => 0, 'max' => 50, 'slider_max' => 25, 'step' => 1, 'label' => 'Annual Step-Up', 'suffix' => '%'],
            'target_corpus'  => ['default' => 10000000, 'min' => 100000, 'max' => 1000000000, 'slider_max' => 50000000, 'step' => 100000, 'label' => 'Target Corpus', 'prefix' => '₹'],
            'inflation'      => ['default' => 0, 'min' => 0, 'max' => 15, 'slider_max' => 12, 'step' => 0.5, 'label' => 'Expected Inflation', 'suffix' => '%'],
            'lumpsum'        => ['default' => 0, 'min' => 0, 'max' => 10000000, 'slider_max' => 100000, 'step' => 5000, 'label' => 'Lumpsum', 'prefix' => '₹'],
            'swp_withdrawal' => ['default' => 25000, 'min' => 0, 'max' => 1000000, 'slider_max' => 200000, 'step' => 500, 'label' => 'Monthly SWP', 'prefix' => '₹'],
            'swp_years'      => ['default' => 20, 'min' => 1, 'max' => 50, 'slider_max' => 40, 'step' => 1, 'label' => 'SWP Period', 'suffix' => 'Yrs'],
            'swp_stepup'     => ['default' => 5, 'min' => 0, 'max' => 20, 'slider_max' => 20, 'step' => 1, 'label' => 'Yearly Hike', 'suffix' => '%'],
            'swp_rate'       => ['default' => 8, 'min' => 1, 'max' => 30, 'slider_max' => 25, 'step' => 1, 'label' => 'SWP Return', 'suffix' => '%'],
            'corpus'         => ['default' => 5000000, 'min' => 10000, 'max' => 100000000, 'slider_max' => 10000000, 'step' => 50000, 'label' => 'Corpus', 'prefix' => '₹']
        ]);
    }

    public function testStrategyFactoryCreatesTargetCorpusStrategy(): void
    {
        $factory = new StrategyFactory($this->configService);
        $strategy = $factory->create('target-corpus-calculator');

        $this->assertInstanceOf(TargetCorpusStrategy::class, $strategy);
        $this->assertSame('target_corpus', $strategy->getType());
    }

    public function testInverseRequiredSipCalculationMatchesTargetCorpus(): void
    {
        // Target: ₹1 Crore in 15 Years @ 12% CAGR with 10% annual Step-Up
        $targetCorpus = 10000000.0;
        $years = 15;
        $rate = 12.0;
        $stepup = 10.0;

        $inputs = InvestmentInputs::fromValues(
            0.0,
            $years,
            $rate,
            $stepup,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $requiredSip = $this->calculator->calculateRequiredSip($inputs, $targetCorpus);

        // Required monthly SIP should be around ₹11,516
        $this->assertGreaterThan(11000.0, $requiredSip);
        $this->assertLessThan(12000.0, $requiredSip);

        // Now run forward simulation with the calculated required SIP
        $forwardInputs = InvestmentInputs::fromValues(
            $requiredSip,
            $years,
            $rate,
            $stepup,
            false,
            0.0,
            0.0,
            0,
            0.0,
            0.0
        );

        $results = $this->calculator->calculate($forwardInputs);
        $finalRow = end($results);
        $finalCorpus = $finalRow['combined_total'];

        // Should achieve target corpus of ₹1 Crore within 0.1% tolerance
        $this->assertGreaterThanOrEqual($targetCorpus * 0.999, $finalCorpus);
        $this->assertLessThanOrEqual($targetCorpus * 1.001, $finalCorpus);
    }
}
