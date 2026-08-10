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
    /** @var array<string, CalculatorStrategyInterface> */
    private array $strategyInstances;

    public function __construct(ConfigService $configService, ?array $strategyMap = null, array $strategyInstances = [])
    {
        $this->configService = $configService;
        $this->strategyMap = $strategyMap ?? self::DEFAULT_STRATEGY_MAP;
        $this->strategyInstances = $strategyInstances;
    }

    public function create(string $slug): CalculatorStrategyInterface
    {
        $key = ltrim($slug, '/');
        $strategyClass = $this->strategyMap[$key] ?? SipStrategy::class;

        if (isset($this->strategyInstances[$strategyClass])) {
            return $this->strategyInstances[$strategyClass];
        }

        return new $strategyClass($this->configService);
    }
}
