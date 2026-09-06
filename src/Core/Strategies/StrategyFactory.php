<?php

declare(strict_types=1);

namespace Core\Strategies;

use Services\ConfigServiceInterface;

class StrategyFactory
{
    private const DEFAULT_STRATEGY_MAP = [
        'sip-calculator'            => SipStrategy::class,
        'swp-calculator'            => SwpStrategy::class,
        'sip-step-up-calculator'    => SipStrategy::class,
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
        'reach-1-crore-via-sip'        => TargetCorpusStrategy::class,
        'reach-5-crore-via-sip'        => TargetCorpusStrategy::class,
        'sip-5000-per-month'           => SipStrategy::class,
        'sip-10000-per-month'          => SipStrategy::class,
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
