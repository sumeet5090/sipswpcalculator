<?php

declare(strict_types=1);

namespace Core;

use Services\SessionManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * ViewRenderer
 * Handles presentation rendering using Twig as an injected service.
 */
class ViewRenderer
{
    private Environment $twig;
    private SessionManager $sessionManager;

    public function __construct(
        SessionManager $sessionManager,
        ViteHelper $viteHelper,
        string $env = 'development',
        string $appUrl = 'https://sipswpcalculator.com'
    ) {
        $this->sessionManager = $sessionManager;
        $isProd = ($env === 'production');

        $loader = new FilesystemLoader(__DIR__ . '/../Views');

        $cachePath = false;
        if ($isProd) {
            $cacheDir = __DIR__ . '/../../var/cache/twig';
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0775, true);
            }
            $cachePath = $cacheDir;
        }

        $this->twig = new Environment($loader, [
            'cache' => $cachePath,
            'debug' => !$isProd,
        ]);

        $this->twig->addGlobal('env', $env);
        $this->twig->addGlobal('site_url', rtrim($appUrl, '/'));

        $this->twig->addFilter(new \Twig\TwigFilter('formatInr', function ($amount) {
            return \Core\CurrencyHelper::formatInr((float) $amount);
        }));
        $this->twig->addFilter(new \Twig\TwigFilter('array_values', function ($array) {
            return is_array($array) ? array_values($array) : $array;
        }));

        $vite = $viteHelper;
        $this->twig->addFunction(new \Twig\TwigFunction('vite_asset', [$vite, 'asset']));
        $this->twig->addFunction(new \Twig\TwigFunction('vite_client', [$vite, 'client'], ['is_safe' => ['html']]));
        $this->twig->addFunction(new \Twig\TwigFunction('vite_css', [$vite, 'css'], ['is_safe' => ['html']]));
    }

    /**
     * Render a template file and return the string content.
     */
    public function render(string $view, array $data = []): string
    {
        $csrfToken = $data['csrf_token'] ?? $this->sessionManager->getCsrfToken();
        $data['csrf_token'] = $csrfToken;
        $data['app'] = $data['app'] ?? ['session' => ['csrf_token' => $csrfToken]];

        // Support extensionless view names, defaulting to .twig
        if (!str_ends_with($view, '.twig')) {
            $view .= '.twig';
        }

        try {
            return $this->twig->render($view, $data);
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
