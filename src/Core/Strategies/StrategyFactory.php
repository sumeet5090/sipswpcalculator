<?php

declare(strict_types=1);

namespace Core\Strategies;

use Core\Config\CalculatorRegistry;
use Services\ConfigServiceInterface;

class StrategyFactory
{
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
        $this->strategyMap = $strategyMap ?? CalculatorRegistry::getStrategyClassMap();
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
        $overrideArgs = CalculatorRegistry::getRouteStrategyArgs($key);

        if (!empty($overrideArgs)) {
            return new $strategyClass($this->configService, ...$overrideArgs);
        }

        if ($this->strategyResolver !== null) {
            $strategy = ($this->strategyResolver)($strategyClass);
            if ($strategy instanceof CalculatorStrategyInterface) {
                return $strategy;
            }
        }

        return new $strategyClass($this->configService);
    }
}
