<?php

declare(strict_types=1);

namespace Core\Config;

use Controllers\RenderGuideAction;
use Core\Strategies\CalculatorStrategyInterface;

/**
 * Single Source of Truth (SSoT) Calculator Registry loaded from content/calculator_registry.json.
 * Eliminates fragmented mode lists across routes, StrategyFactory, DI providers, Twig templates, and JS bundles.
 */
final class CalculatorRegistry
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, mixed>
     */
    private static function load(): array
    {
        if (self::$cache === null) {
            $path = dirname(__DIR__, 3) . '/content/calculator_registry.json';
            $raw = file_exists($path) ? file_get_contents($path) : false;
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                throw new \RuntimeException("Failed to load calculator registry at: {$path}");
            }
            self::$cache = $decoded;
        }

        return self::$cache;
    }

    public static function resetCache(): void
    {
        self::$cache = null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getModes(): array
    {
        $data = self::load();
        $modes = $data['modes'] ?? [];
        return is_array($modes) ? $modes : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getModeMetadata(string $mode): ?array
    {
        $modes = self::getModes();
        $entry = $modes[$mode] ?? null;
        return is_array($entry) ? $entry : null;
    }

    /**
     * @return list<string>
     */
    public static function getSpecializedModeNames(): array
    {
        $names = [];
        foreach (self::getModes() as $mode => $meta) {
            if (!empty($meta['is_specialized'])) {
                $names[] = (string) $mode;
            }
        }
        return $names;
    }

    /**
     * @return list<string>
     */
    public static function getFourCardModeNames(): array
    {
        $names = [];
        foreach (self::getModes() as $mode => $meta) {
            if (!empty($meta['is_four_card'])) {
                $names[] = (string) $mode;
            }
        }
        return $names;
    }

    public static function isSpecialized(string $mode): bool
    {
        $meta = self::getModeMetadata($mode);
        return $meta !== null && !empty($meta['is_specialized']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getRoutes(): array
    {
        $data = self::load();
        $routes = $data['routes'] ?? [];
        return is_array($routes) ? $routes : [];
    }

    /**
     * Build the exact `'calculators'` array expected by routes.php and tests.
     *
     * @return array<string, array{action: array{0: class-string, 1: string}, priority: string, changefreq: string}>
     */
    public static function getRouterCalculatorDefinitions(): array
    {
        $definitions = [];
        foreach (self::getRoutes() as $path => $cfg) {
            $definitions[(string) $path] = [
                'action'     => [RenderGuideAction::class, '__invoke'],
                'priority'   => (string) ($cfg['priority'] ?? '0.8'),
                'changefreq' => (string) ($cfg['changefreq'] ?? 'monthly'),
            ];
        }
        return $definitions;
    }

    /**
     * Build slug => strategy class map for StrategyFactory.
     *
     * @return array<string, class-string<CalculatorStrategyInterface>>
     */
    public static function getStrategyClassMap(): array
    {
        $map = [];
        foreach (self::getRoutes() as $path => $cfg) {
            $slug = ltrim((string) $path, '/');
            $rawClass = $cfg['strategy_class'] ?? '';
            if ($slug !== '' && is_string($rawClass) && $rawClass !== '') {
                /** @var class-string<CalculatorStrategyInterface> $strategyClass */
                $strategyClass = $rawClass;
                $map[$slug] = $strategyClass;
            }
        }
        return $map;
    }

    /**
     * Get constructor override arguments (if any) for specialized slug variants.
     *
     * @return array<int, float|int|string|bool>
     */
    public static function getRouteStrategyArgs(string $slug): array
    {
        $key = '/' . ltrim($slug, '/');
        $routes = self::getRoutes();
        $args = $routes[$key]['strategy_args'] ?? [];
        return is_array($args) ? array_values($args) : [];
    }

    /**
     * Return unique strategy FQCNs registered across modes and routes.
     *
     * @return list<class-string<CalculatorStrategyInterface>>
     */
    public static function getUniqueStrategyClasses(): array
    {
        $classes = [];
        foreach (self::getModes() as $meta) {
            if (!empty($meta['strategy_class']) && is_string($meta['strategy_class'])) {
                /** @var class-string<CalculatorStrategyInterface> $fqcn */
                $fqcn = $meta['strategy_class'];
                $classes[$fqcn] = $fqcn;
            }
        }
        foreach (self::getRoutes() as $cfg) {
            if (!empty($cfg['strategy_class']) && is_string($cfg['strategy_class'])) {
                /** @var class-string<CalculatorStrategyInterface> $fqcn */
                $fqcn = $cfg['strategy_class'];
                $classes[$fqcn] = $fqcn;
            }
        }
        return array_values($classes);
    }

    public static function getCalculatorRouteCount(): int
    {
        return count(self::getRoutes());
    }

    public static function getSpecializedCalculatorCount(): int
    {
        return count(self::getSpecializedModeNames());
    }

    public static function isCalculatorRouteSlug(string $slug): bool
    {
        $normalized = trim($slug);
        if ($normalized === 'home' || $normalized === '/') {
            return true;
        }
        if ($normalized === '') {
            return false;
        }

        $routePath = '/' . ltrim($normalized, '/');
        return array_key_exists($routePath, self::getRoutes());
    }
}
