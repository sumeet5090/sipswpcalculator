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
        string $env,
        string $appUrl,
        ?string $viewsPath = null,
        ?string $cachePath = null,
        ?CurrencyFormatterInterface $currencyFormatter = null,
        ?\Core\Twig\AppTwigExtension $twigExtension = null
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
        $extension = $twigExtension ?? new \Core\Twig\AppTwigExtension($viteHelper, $currencyFormatter);
        $this->twig->addExtension($extension);
    }

    private function normalizeViewName(string $view): string
    {
        return str_ends_with($view, '.twig') ? $view : $view . '.twig';
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
            'request' => \Core\Http\Request::createFromGlobals(),
        ], $data);

        $resolvedView = $this->normalizeViewName($view);

        try {
            return $this->twig->render($resolvedView, $renderData);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Twig rendering failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the modification date of a view template file.
     */
    public function getTemplateModifiedDate(string $view): string
    {
        $resolvedView = $this->normalizeViewName($view);
        try {
            $source = $this->twig->getLoader()->getSourceContext($resolvedView);
            $filePath = $source->getPath();
            if (empty($filePath) || !file_exists($filePath)) {
                throw new \RuntimeException("View template file missing at path: '{$filePath}' for view: '{$resolvedView}'");
            }
            return date('Y-m-d', filemtime($filePath));
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
            throw new \RuntimeException("Failed to get modification date for template '{$resolvedView}': " . $e->getMessage(), 0, $e);
        }
    }
}
