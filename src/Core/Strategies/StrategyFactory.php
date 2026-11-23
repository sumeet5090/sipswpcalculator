<?php

declare(strict_types=1);

namespace Core\Strategies;

use Services\ConfigServiceInterface;

class StrategyFactory
{
    private const DEFAULT_STRATEGY_MAP = [
        'sip-calculator'            => SipStrategy::class,
        'swp-calculator'            => SwpStrategy::class,
        'sip-step-up-calculator'    => StepUpSipStrategy::class,
        'lumpsum-calculator'        => LumpsumStrategy::class,
        'retirement-calculator'        => ComboStrategy::class,
        'my-first-crore-calculator'    => TargetCorpusStrategy::class,
        'target-corpus-calculator'     => TargetCorpusStrategy::class,
        'compound-interest-calculator' => CompoundInterestStrategy::class,
        'cagr-calculator'              => CagrStrategy::class,
        'emi-calculator'               => EmiStrategy::class,
        'inflation-calculator'         => InflationStrategy::class,
        'ppf-calculator'               => PpfStrategy::class,
        'fd-calculator'                => FdStrategy::class,
        'reach-5-crore-via-sip'        => TargetCorpusStrategy::class,
        'sip-5000-per-month'           => SipStrategy::class,
        'sip-10000-per-month'          => SipStrategy::class,
        '1-crore-swp-calculator'       => SwpStrategy::class,
        '2-crore-sip-calculator'       => TargetCorpusStrategy::class,
        'sip-25000-per-month'          => SipStrategy::class,
        '50-lakh-swp-calculator'       => SwpStrategy::class,
        'sip-50000-per-month'          => SipStrategy::class,
        'sip-15000-per-month'          => SipStrategy::class,
        'sip-20000-per-month'          => SipStrategy::class,
        '3-crore-sip-calculator'       => TargetCorpusStrategy::class,
        '25-lakh-swp-calculator'       => SwpStrategy::class,
        '2-crore-swp-calculator'       => SwpStrategy::class,
        '75-lakh-swp-calculator'       => SwpStrategy::class,
        'sip-1-lakh-per-month'         => SipStrategy::class,
    ];

    private ConfigServiceInterface $configService;
    /** @var array<string, class-string<CalculatorStrategyInterface>> */
    private array $strategyMap;
    /** @var (callable(string): ?CalculatorStrategyInterface)|null */
    private $strategyResolver;

    /**
     * @param ConfigServiceInterface $configService
     * @param array<string, class-string<CalculatorStrategyInterface>>|null $strategyMap
     * @param (callable(string): ?CalculatorStrategyInterface)|null $strategyResolver
     */
    public function __construct(
        ConfigServiceInterface $configService,
        ?array $strategyMap = null,
        ?callable $strategyResolver = null
    ) {
        $this->configService = $configService;
        $this->strategyMap = $strategyMap ?? self::DEFAULT_STRATEGY_MAP;
        $this->strategyResolver = $strategyResolver;
    }

    public function create(string $slug): CalculatorStrategyInterface
    {
        $key = basename(ltrim($slug, '/'));
        if ($key === '') {
            $key = 'sip-calculator';
        }
        if (!isset($this->strategyMap[$key])) {
            throw new \DomainException("No calculator strategy mapped for slug: '{$key}'");
        }

        if ($key === 'sip-5000-per-month') {
            return new SipStrategy($this->configService, 5000.0);
        }
        if ($key === 'reach-5-crore-via-sip') {
            return new TargetCorpusStrategy($this->configService, 50000000.0);
        }
        if ($key === '1-crore-swp-calculator') {
            return new SwpStrategy($this->configService, 10000000.0, 65000.0);
        }
        if ($key === '2-crore-sip-calculator') {
            return new TargetCorpusStrategy($this->configService, 20000000.0);
        }
        if ($key === 'sip-25000-per-month') {
            return new SipStrategy($this->configService, 25000.0);
        }
        if ($key === '50-lakh-swp-calculator') {
            return new SwpStrategy($this->configService, 5000000.0, 32500.0);
        }
        if ($key === 'sip-50000-per-month') {
            return new SipStrategy($this->configService, 50000.0);
        }
        if ($key === 'sip-15000-per-month') {
            return new SipStrategy($this->configService, 15000.0);
        }
        if ($key === 'sip-20000-per-month') {
            return new SipStrategy($this->configService, 20000.0);
        }
        if ($key === '3-crore-sip-calculator') {
            return new TargetCorpusStrategy($this->configService, 30000000.0);
        }
        if ($key === '25-lakh-swp-calculator') {
            return new SwpStrategy($this->configService, 2500000.0, 16250.0);
        }
        if ($key === '2-crore-swp-calculator') {
            return new SwpStrategy($this->configService, 20000000.0, 130000.0);
        }
        if ($key === '75-lakh-swp-calculator') {
            return new SwpStrategy($this->configService, 7500000.0, 48750.0);
        }
        if ($key === 'sip-1-lakh-per-month') {
            return new SipStrategy($this->configService, 100000.0);
        }

        $strategyClass = $this->strategyMap[$key];

        if ($this->strategyResolver !== null) {
            $strategy = ($this->strategyResolver)($strategyClass);
            if ($strategy instanceof CalculatorStrategyInterface) {
                return $strategy;
            }
        }

        return new $strategyClass($this->configService);
    }
}
