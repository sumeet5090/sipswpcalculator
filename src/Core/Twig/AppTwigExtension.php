<?php

declare(strict_types=1);

namespace Core\Twig;

use Core\Config\CalculatorRegistry;
use Core\CurrencyFormatterInterface;
use Core\ViteHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * AppTwigExtension
 * Custom Twig extension for application-level filters and functions.
 */
class AppTwigExtension extends AbstractExtension
{
    private ViteHelper $viteHelper;
    private CurrencyFormatterInterface $currencyFormatter;

    public function __construct(ViteHelper $viteHelper, CurrencyFormatterInterface $currencyFormatter)
    {
        $this->viteHelper = $viteHelper;
        $this->currencyFormatter = $currencyFormatter;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('formatInr', function ($amount): string {
                if (is_string($amount)) {
                    $cleaned = str_replace([',', ' '], '', $amount);
                    if (is_numeric($cleaned)) {
                        return $this->currencyFormatter->format((float) $cleaned);
                    }
                }
                return $this->currencyFormatter->format((float) $amount);
            }),
            new TwigFilter('array_values', fn($array) => is_array($array) ? array_values($array) : $array),
            new TwigFilter('json_island', function ($data): string {
                return (string) json_encode(
                    $data,
                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR
                );
            }, ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_asset', [$this->viteHelper, 'asset']),
            new TwigFunction('vite_client', [$this->viteHelper, 'client'], ['is_safe' => ['html']]),
            new TwigFunction('vite_css', [$this->viteHelper, 'css'], ['is_safe' => ['html']]),
            new TwigFunction('vite_js', [$this->viteHelper, 'js'], ['is_safe' => ['html']]),
            new TwigFunction('calculator_mode_meta', fn(string $mode): ?array => CalculatorRegistry::getModeMetadata($mode)),
            new TwigFunction('is_specialized_mode', fn(string $mode): bool => CalculatorRegistry::isSpecialized($mode)),
            new TwigFunction('is_four_card_mode', fn(string $mode): bool => (bool) (CalculatorRegistry::getModeMetadata($mode)['is_four_card'] ?? false)),
            new TwigFunction('calculator_route_count', fn(): int => CalculatorRegistry::getCalculatorRouteCount()),
            new TwigFunction('specialized_calculator_count', fn(): int => CalculatorRegistry::getSpecializedCalculatorCount()),
            new TwigFunction('is_calculator_route', fn(string $slug): bool => CalculatorRegistry::isCalculatorRouteSlug($slug)),
            new TwigFunction('supports_tax_waterfall', fn(string $mode): bool => CalculatorRegistry::supportsTaxWaterfall($mode)),
            new TwigFunction('supports_post_tax_toggle', fn(string $mode): bool => CalculatorRegistry::supportsPostTaxToggle($mode)),
            new TwigFunction('supports_snapshot', fn(string $mode): bool => CalculatorRegistry::supportsSnapshot($mode)),
            new TwigFunction('supports_amfi_badge', fn(string $mode): bool => CalculatorRegistry::supportsAmfiBadge($mode)),
        ];
    }
}
