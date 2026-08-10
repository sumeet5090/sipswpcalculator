<?php

declare(strict_types=1);

namespace Core\Twig;

use Core\CurrencyFormatterInterface;
use Core\CurrencyHelper;
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

    public function __construct(ViteHelper $viteHelper, ?CurrencyFormatterInterface $currencyFormatter = null)
    {
        $this->viteHelper = $viteHelper;
        $this->currencyFormatter = $currencyFormatter ?? new CurrencyHelper();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('formatInr', fn($amount) => $this->currencyFormatter->format((float) $amount)),
            new TwigFilter('array_values', fn($array) => is_array($array) ? array_values($array) : $array),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_asset', [$this->viteHelper, 'asset']),
            new TwigFunction('vite_client', [$this->viteHelper, 'client'], ['is_safe' => ['html']]),
            new TwigFunction('vite_css', [$this->viteHelper, 'css'], ['is_safe' => ['html']]),
        ];
    }
}
