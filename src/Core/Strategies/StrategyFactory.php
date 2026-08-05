<?php

declare(strict_types=1);

namespace Core\Strategies;

use Services\ConfigService;

class StrategyFactory
{
    private const STRATEGY_MAP = [
        'sip-calculator'            => SipStrategy::class,
        'swp-calculator'            => SwpStrategy::class,
        'sip-step-up-calculator'    => SipStrategy::class,
        'lumpsum-calculator'        => LumpsumStrategy::class,
        'retirement-calculator'     => ComboStrategy::class,
        'my-first-crore-calculator' => TargetCorpusStrategy::class,
    ];

    private ConfigService $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function create(string $slug): CalculatorStrategyInterface
    {
        $key = ltrim($slug, '/');
        $strategyClass = self::STRATEGY_MAP[$key] ?? SipStrategy::class;

        return new $strategyClass($this->configService);
    }
}
