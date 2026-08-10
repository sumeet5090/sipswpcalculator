<?php

declare(strict_types=1);

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * ViewRenderer
 * Handles presentation rendering using Twig as an injected service.
 */
class ViewRenderer
{
    private Environment $twig;

    public function __construct(
        ViteHelper $viteHelper,
        string $env = 'development',
        string $appUrl = 'https://sipswpcalculator.com',
        ?string $viewsPath = null,
        ?string $cachePath = null,
        ?CurrencyFormatterInterface $currencyFormatter = null
    ) {
        $isProd = ($env === 'production');

        $resolvedViews = $viewsPath ?? (__DIR__ . '/../Views');
        $loader = new FilesystemLoader($resolvedViews);

        $effectiveCache = false;
        if ($cachePath !== null) {
            $effectiveCache = $cachePath;
        } elseif ($isProd) {
            $cacheDir = __DIR__ . '/../../var/cache/twig';
            if (is_dir($cacheDir)) {
                $effectiveCache = $cacheDir;
            }
        }

        $this->twig = new Environment($loader, [
            'cache' => $effectiveCache,
            'debug' => !$isProd,
        ]);

        $this->twig->addGlobal('env', $env);
        $this->twig->addGlobal('site_url', rtrim($appUrl, '/'));
        $this->twig->addExtension(new \Core\Twig\AppTwigExtension($viteHelper, $currencyFormatter));
    }

    /**
     * Render a template file and return the string content.
     */
    public function render(string $view, array $data = []): string
    {
        $csrfToken = $data['csrf_token'] ?? '';
        $renderData = array_merge([
            'csrf_token' => $csrfToken,
            'app' => ['session' => ['csrf_token' => $csrfToken]],
        ], $data);

        // Support extensionless view names, defaulting to .twig
        if (!str_ends_with($view, '.twig')) {
            $view .= '.twig';
        }

        try {
            return $this->twig->render($view, $renderData);
        } catch (\Exception $e) {
            throw new \RuntimeException("Twig rendering failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the modification date of a view template file.
     */
    public function getTemplateModifiedDate(string $view, ?string $fallbackDate = null): string
    {
        if (!str_ends_with($view, '.twig')) {
            $view .= '.twig';
        }
        $filePath = __DIR__ . '/../Views/' . ltrim($view, '/');
        $fallback = $fallbackDate ?? date('Y-m-d');
        return file_exists($filePath) ? date('Y-m-d', filemtime($filePath)) : $fallback;
    }
}
