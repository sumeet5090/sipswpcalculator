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
        if ($cachePath !== null && is_dir($cachePath) && is_writable($cachePath)) {
            $effectiveCache = $cachePath;
        } elseif ($isProd) {
            $cacheDir = __DIR__ . '/../../var/cache/twig';
            if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
                error_log("ViewRenderer: Failed to create Twig cache directory at {$cacheDir}");
            }
            if (is_dir($cacheDir) && is_writable($cacheDir)) {
                $effectiveCache = $cacheDir;
            }
        }

        $this->twig = new Environment($loader, [
            'cache' => $effectiveCache,
            'debug' => !$isProd,
            'auto_reload' => !$isProd,
        ]);

        $this->twig->addGlobal('env', $env);
        $this->twig->addGlobal('site_url', rtrim($appUrl, '/'));
        $this->twig->addGlobal('theme_tokens', \Core\Config\ThemeConstants::getTokens());
        $formatter = $currencyFormatter ?? new \Core\CurrencyHelper();
        $extension = $twigExtension ?? new \Core\Twig\AppTwigExtension($viteHelper, $formatter);
        $this->twig->addExtension($extension);
    }

    private function normalizeViewName(string $view): string
    {
        return str_ends_with($view, '.twig') ? $view : $view . '.twig';
    }

    /**
     * Render a template file and return the string content.
     *
     * @param string $view Template path/name
     * @param array<string, mixed> $data Template context variables
     * @param \Core\Http\Request|null $request Optional request instance
     * @return string Rendered HTML
     */
    public function render(string $view, array $data = [], ?\Core\Http\Request $request = null): string
    {
        $csrfToken = $data['csrf_token'] ?? '';
        $effectiveRequest = $request ?? ($data['request'] ?? null);
        if ($effectiveRequest === null) {
            $effectiveRequest = \Core\Http\Request::createFromGlobals();
        }

        $renderData = array_merge([
            'csrf_token' => $csrfToken,
            'app' => ['session' => ['csrf_token' => $csrfToken]],
            'request' => $effectiveRequest,
        ], $data);

        $resolvedView = $this->normalizeViewName($view);

        try {
            return $this->twig->render($resolvedView, $renderData);
        } catch (\Twig\Error\LoaderError $e) {
            throw new \Core\Exceptions\NotFoundException("Template '{$resolvedView}' not found: " . $e->getMessage(), 0, $e);
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
                throw new \Core\Exceptions\NotFoundException("View template file missing at path: '{$filePath}' for view: '{$resolvedView}'");
            }
            return date('Y-m-d', (int) filemtime($filePath));
        } catch (\Twig\Error\LoaderError $e) {
            throw new \Core\Exceptions\NotFoundException("View template '{$resolvedView}' not found: " . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            if ($e instanceof \Core\Exceptions\NotFoundException || $e instanceof \RuntimeException) {
                throw $e;
            }
            throw new \RuntimeException("Failed to get modification date for template '{$resolvedView}': " . $e->getMessage(), 0, $e);
        }
    }
}
