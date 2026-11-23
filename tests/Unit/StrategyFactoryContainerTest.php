<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\App;
use Core\Strategies\ComboStrategy;
use Core\Strategies\LumpsumStrategy;
use Core\Strategies\SipStrategy;
use Core\Strategies\StrategyFactory;
use Core\Strategies\SwpStrategy;
use Core\Strategies\TargetCorpusStrategy;
use PHPUnit\Framework\TestCase;
use Services\ConfigService;

class StrategyFactoryContainerTest extends TestCase
{
    private StrategyFactory $factory;

    protected function setUp(): void
    {
        $container = App::createContainer();
        $this->factory = $container->get(StrategyFactory::class);
    }

    public function testCreateSipStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('sip-calculator');
        $this->assertInstanceOf(SipStrategy::class, $strategy);
        $this->assertSame('sip', $strategy->getType());
    }

    public function testCreateSwpStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('swp-calculator');
        $this->assertInstanceOf(SwpStrategy::class, $strategy);
        $this->assertSame('swp', $strategy->getType());
    }

    public function testCreateLumpsumStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('lumpsum-calculator');
        $this->assertInstanceOf(LumpsumStrategy::class, $strategy);
        $this->assertSame('lumpsum', $strategy->getType());
    }

    public function testCreateTargetCorpusStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('my-first-crore-calculator');
        $this->assertInstanceOf(TargetCorpusStrategy::class, $strategy);
        $this->assertSame('target_corpus', $strategy->getType());
    }

    public function testCreateRetirementCalculatorUsesComboStrategy(): void
    {
        $strategy = $this->factory->create('retirement-calculator');
        $this->assertInstanceOf(ComboStrategy::class, $strategy);
        $this->assertSame('combo', $strategy->getType());
    }

    public function testCreateCompoundInterestStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('compound-interest-calculator');
        $this->assertInstanceOf(\Core\Strategies\CompoundInterestStrategy::class, $strategy);
        $this->assertSame('compound_interest', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\Inputs\CalculatorInputsInterface::class, $inputs);
        $this->assertInstanceOf(\Core\Inputs\CompoundInterestInputs::class, $inputs);
        $this->assertArrayHasKey('ci_principal', $inputs->toTemplateData());
    }

    public function testCreateCagrStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('cagr-calculator');
        $this->assertInstanceOf(\Core\Strategies\CagrStrategy::class, $strategy);
        $this->assertSame('cagr', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\Inputs\CalculatorInputsInterface::class, $inputs);
        $this->assertInstanceOf(\Core\Inputs\CagrInputs::class, $inputs);
        $this->assertArrayHasKey('cagr_initial', $inputs->toTemplateData());
    }

    public function testCreateEmiStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('emi-calculator');
        $this->assertInstanceOf(\Core\Strategies\EmiStrategy::class, $strategy);
        $this->assertSame('emi', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\Inputs\CalculatorInputsInterface::class, $inputs);
        $this->assertInstanceOf(\Core\Inputs\EmiInputs::class, $inputs);
        $this->assertArrayHasKey('emi_principal', $inputs->toTemplateData());
    }

    public function testCreateInflationStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('inflation-calculator');
        $this->assertInstanceOf(\Core\Strategies\InflationStrategy::class, $strategy);
        $this->assertSame('inflation', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\Inputs\CalculatorInputsInterface::class, $inputs);
        $this->assertInstanceOf(\Core\Inputs\InflationInputs::class, $inputs);
        $this->assertArrayHasKey('inf_amount', $inputs->toTemplateData());
    }

    public function testCreatePpfStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('ppf-calculator');
        $this->assertInstanceOf(\Core\Strategies\PpfStrategy::class, $strategy);
        $this->assertSame('ppf', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\Inputs\CalculatorInputsInterface::class, $inputs);
        $this->assertInstanceOf(\Core\Inputs\PpfInputs::class, $inputs);
        $this->assertArrayHasKey('ppf_annual_investment', $inputs->toTemplateData());
    }

    public function testCreateFdStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('fd-calculator');
        $this->assertInstanceOf(\Core\Strategies\FdStrategy::class, $strategy);
        $this->assertSame('fd', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\Inputs\CalculatorInputsInterface::class, $inputs);
        $this->assertInstanceOf(\Core\Inputs\FdInputs::class, $inputs);
        $this->assertArrayHasKey('fd_amount', $inputs->toTemplateData());
    }

    public function testCreateStepUpSipStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('sip-step-up-calculator');
        $this->assertInstanceOf(\Core\Strategies\StepUpSipStrategy::class, $strategy);
        $this->assertSame('sip-step-up', $strategy->getType());
        $this->assertSame('components/benchmarks/sip-stepup-comparison.twig', $strategy->getBenchmarkTemplate());
        $this->assertSame('Step-Up SIP vs. Regular SIP Wealth Generation Comparison', $strategy->getBenchmarkTitle());
    }

    public function testStrategyBenchmarkTemplatesAndTitles(): void
    {
        $sip = $this->factory->create('sip-calculator');
        $this->assertSame('components/benchmarks/sip-swp-dual.twig', $sip->getBenchmarkTemplate());

        $swp = $this->factory->create('swp-calculator');
        $this->assertSame('components/benchmarks/swp-longevity.twig', $swp->getBenchmarkTemplate());

        $lumpsum = $this->factory->create('lumpsum-calculator');
        $this->assertSame('components/benchmarks/lumpsum-growth.twig', $lumpsum->getBenchmarkTemplate());

        $cagr = $this->factory->create('cagr-calculator');
        $this->assertSame('components/benchmarks/cagr-historical.twig', $cagr->getBenchmarkTemplate());

        $emi = $this->factory->create('emi-calculator');
        $this->assertSame('components/benchmarks/emi-amortization.twig', $emi->getBenchmarkTemplate());

        $ppf = $this->factory->create('ppf-calculator');
        $this->assertSame('components/benchmarks/ppf-maturity.twig', $ppf->getBenchmarkTemplate());

        $fd = $this->factory->create('fd-calculator');
        $this->assertSame('components/benchmarks/fd-compounding.twig', $fd->getBenchmarkTemplate());

        $inflation = $this->factory->create('inflation-calculator');
        $this->assertSame('components/benchmarks/inflation-erosion.twig', $inflation->getBenchmarkTemplate());

        $targetCorpus = $this->factory->create('my-first-crore-calculator');
        $this->assertSame('components/benchmarks/target-corpus.twig', $targetCorpus->getBenchmarkTemplate());
    }

    public function testCreate1CroreSwpStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('1-crore-swp-calculator');
        $this->assertInstanceOf(SwpStrategy::class, $strategy);
        $this->assertSame('swp', $strategy->getType());
        $this->assertSame(10000000.0, $strategy->getDefaultCorpus());
        $this->assertSame(65000.0, $strategy->getDefaultWithdrawal());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(10000000.0, $inputs->getStartingCorpus());
        $this->assertSame(65000.0, $inputs->getSwpWithdrawal());
    }

    public function testCreate2CroreSipStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('2-crore-sip-calculator');
        $this->assertInstanceOf(TargetCorpusStrategy::class, $strategy);
        $this->assertSame('target_corpus', $strategy->getType());
        $this->assertSame(20000000.0, $strategy->getTargetCorpus());
    }

    public function testCreateSip25000StrategyFromContainer(): void
    {
        $strategy = $this->factory->create('sip-25000-per-month');
        $this->assertInstanceOf(SipStrategy::class, $strategy);
        $this->assertSame('sip', $strategy->getType());
        $this->assertSame(25000.0, $strategy->getDefaultSip());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(25000.0, $inputs->getSip());
    }

    public function testCreate50LakhSwpStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('50-lakh-swp-calculator');
        $this->assertInstanceOf(SwpStrategy::class, $strategy);
        $this->assertSame('swp', $strategy->getType());
        $this->assertSame(5000000.0, $strategy->getDefaultCorpus());
        $this->assertSame(32500.0, $strategy->getDefaultWithdrawal());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(5000000.0, $inputs->getStartingCorpus());
        $this->assertSame(32500.0, $inputs->getSwpWithdrawal());
    }

    public function testCreateSip50000StrategyFromContainer(): void
    {
        $strategy = $this->factory->create('sip-50000-per-month');
        $this->assertInstanceOf(SipStrategy::class, $strategy);
        $this->assertSame('sip', $strategy->getType());
        $this->assertSame(50000.0, $strategy->getDefaultSip());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(50000.0, $inputs->getSip());
    }

    public function testCreateSip15000StrategyFromContainer(): void
    {
        $strategy = $this->factory->create('sip-15000-per-month');
        $this->assertInstanceOf(SipStrategy::class, $strategy);
        $this->assertSame('sip', $strategy->getType());
        $this->assertSame(15000.0, $strategy->getDefaultSip());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(15000.0, $inputs->getSip());
    }

    public function testCreateSip20000StrategyFromContainer(): void
    {
        $strategy = $this->factory->create('sip-20000-per-month');
        $this->assertInstanceOf(SipStrategy::class, $strategy);
        $this->assertSame('sip', $strategy->getType());
        $this->assertSame(20000.0, $strategy->getDefaultSip());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(20000.0, $inputs->getSip());
    }

    public function testCreate3CroreSipStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('3-crore-sip-calculator');
        $this->assertInstanceOf(TargetCorpusStrategy::class, $strategy);
        $this->assertSame('target_corpus', $strategy->getType());
        $this->assertSame(30000000.0, $strategy->getTargetCorpus());
    }

    public function testCreate25LakhSwpStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('25-lakh-swp-calculator');
        $this->assertInstanceOf(SwpStrategy::class, $strategy);
        $this->assertSame('swp', $strategy->getType());
        $this->assertSame(2500000.0, $strategy->getDefaultCorpus());
        $this->assertSame(16250.0, $strategy->getDefaultWithdrawal());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(2500000.0, $inputs->getStartingCorpus());
        $this->assertSame(16250.0, $inputs->getSwpWithdrawal());
    }

    public function testCreate2CroreSwpStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('2-crore-swp-calculator');
        $this->assertInstanceOf(SwpStrategy::class, $strategy);
        $this->assertSame('swp', $strategy->getType());
        $this->assertSame(20000000.0, $strategy->getDefaultCorpus());
        $this->assertSame(130000.0, $strategy->getDefaultWithdrawal());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(20000000.0, $inputs->getStartingCorpus());
        $this->assertSame(130000.0, $inputs->getSwpWithdrawal());
    }

    public function testCreate75LakhSwpStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('75-lakh-swp-calculator');
        $this->assertInstanceOf(SwpStrategy::class, $strategy);
        $this->assertSame('swp', $strategy->getType());
        $this->assertSame(7500000.0, $strategy->getDefaultCorpus());
        $this->assertSame(48750.0, $strategy->getDefaultWithdrawal());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(7500000.0, $inputs->getStartingCorpus());
        $this->assertSame(48750.0, $inputs->getSwpWithdrawal());
    }

    public function testCreate1LakhSipStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('sip-1-lakh-per-month');
        $this->assertInstanceOf(SipStrategy::class, $strategy);
        $this->assertSame('sip', $strategy->getType());
        $this->assertSame(100000.0, $strategy->getDefaultSip());
        $inputs = $strategy->getInitialInputs();
        $this->assertSame(100000.0, $inputs->getSip());
    }

    public function testCreateThrowsForUnmappedSlug(): void
    {
        $this->expectException(\DomainException::class);
        $this->factory->create('unknown-unmapped-calculator-slug');
    }
}
