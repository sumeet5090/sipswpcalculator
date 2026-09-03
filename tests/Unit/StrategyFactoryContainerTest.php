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
        $configService = $container->get(ConfigService::class);
        $this->factory = new StrategyFactory($configService, null, $container);
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
        $this->assertInstanceOf(\Core\InvestmentInputs::class, $inputs);
    }

    public function testCreateCagrStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('cagr-calculator');
        $this->assertInstanceOf(\Core\Strategies\CagrStrategy::class, $strategy);
        $this->assertSame('cagr', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\InvestmentInputs::class, $inputs);
    }

    public function testCreateEmiStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('emi-calculator');
        $this->assertInstanceOf(\Core\Strategies\EmiStrategy::class, $strategy);
        $this->assertSame('emi', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\InvestmentInputs::class, $inputs);
    }

    public function testCreateInflationStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('inflation-calculator');
        $this->assertInstanceOf(\Core\Strategies\InflationStrategy::class, $strategy);
        $this->assertSame('inflation', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\InvestmentInputs::class, $inputs);
    }

    public function testCreatePpfStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('ppf-calculator');
        $this->assertInstanceOf(\Core\Strategies\PpfStrategy::class, $strategy);
        $this->assertSame('ppf', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\InvestmentInputs::class, $inputs);
    }

    public function testCreateFdStrategyFromContainer(): void
    {
        $strategy = $this->factory->create('fd-calculator');
        $this->assertInstanceOf(\Core\Strategies\FdStrategy::class, $strategy);
        $this->assertSame('fd', $strategy->getType());
        $inputs = $strategy->getInitialInputs();
        $this->assertInstanceOf(\Core\InvestmentInputs::class, $inputs);
    }

    public function testCreateThrowsForUnmappedSlug(): void
    {
        $this->expectException(\DomainException::class);
        $this->factory->create('unknown-unmapped-calculator-slug');
    }
}
