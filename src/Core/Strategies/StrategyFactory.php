<?php

declare(strict_types=1);

namespace Core\Strategies;

use Services\ConfigService;

class StrategyFactory
{
    private const DEFAULT_STRATEGY_MAP = [
        'sip-calculator'            => SipStrategy::class,
        'swp-calculator'            => SwpStrategy::class,
        'sip-step-up-calculator'    => SipStrategy::class,
        'lumpsum-calculator'        => LumpsumStrategy::class,
        'retirement-calculator'     => ComboStrategy::class,
        'my-first-crore-calculator' => TargetCorpusStrategy::class,
    ];

    private ConfigService $configService;
    private array $strategyMap;

    public function __construct(ConfigService $configService, ?array $strategyMap = null)
    {
        $this->configService = $configService;
        $this->strategyMap = $strategyMap ?? self::DEFAULT_STRATEGY_MAP;
    }

    public function create(string $slug): CalculatorStrategyInterface
    {
        $key = ltrim($slug, '/');
        $strategyClass = $this->strategyMap[$key] ?? SipStrategy::class;

        return new $strategyClass($this->configService);
    }
}
