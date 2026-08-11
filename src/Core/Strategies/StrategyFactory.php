<?php

declare(strict_types=1);

namespace Core\Strategies;

use Psr\Container\ContainerInterface;
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
    private ?ContainerInterface $container;

    public function __construct(
        ConfigService $configService,
        ?array $strategyMap = null,
        ?ContainerInterface $container = null
    ) {
        $this->configService = $configService;
        $this->strategyMap = $strategyMap ?? self::DEFAULT_STRATEGY_MAP;
        $this->container = $container;
    }

    public function create(string $slug): CalculatorStrategyInterface
    {
        $key = ltrim($slug, '/');
        if (!isset($this->strategyMap[$key])) {
            throw new \DomainException("No calculator strategy mapped for slug: '{$key}'");
        }
        $strategyClass = $this->strategyMap[$key];

        if ($this->container !== null && $this->container->has($strategyClass)) {
            /** @var CalculatorStrategyInterface $strategy */
            $strategy = $this->container->get($strategyClass);
            return $strategy;
        }

        return new $strategyClass($this->configService);
    }
}
