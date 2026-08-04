<?php

declare(strict_types=1);

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * View
 * Handles presentation rendering using Twig.
 */
class View
{
    private static ?Environment $twig = null;

    private static function getTwig(): Environment
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader(__DIR__ . '/../Views');
            $env = Env::get('ENVIRONMENT', 'development');
            $isProd = ($env === 'production');

            $cachePath = false;
            if ($isProd) {
                $cacheDir = __DIR__ . '/../../var/cache/twig';
                if (!file_exists($cacheDir)) {
                    @mkdir($cacheDir, 0775, true);
                }
                $cachePath = $cacheDir;
            }

            self::$twig = new Environment($loader, [
                'cache' => $cachePath,
                'debug' => !$isProd,
            ]);

            self::$twig->addGlobal('env', $env);
            self::$twig->addGlobal('site_url', rtrim((string) Env::get('APP_URL', 'https://sipswpcalculator.com'), '/'));

            self::$twig->addFilter(new \Twig\TwigFilter('formatInr', function ($amount) {
                return \Core\CurrencyHelper::formatInr((float) $amount);
            }));
            self::$twig->addFilter(new \Twig\TwigFilter('array_values', function ($array) {
                return is_array($array) ? array_values($array) : $array;
            }));

            // Vite Integration
            self::$twig->addFunction(new \Twig\TwigFunction('vite_asset', ['\Core\ViteHelper', 'asset']));
            self::$twig->addFunction(new \Twig\TwigFunction('vite_client', ['\Core\ViteHelper', 'client'], ['is_safe' => ['html']]));
            self::$twig->addFunction(new \Twig\TwigFunction('vite_css', ['\Core\ViteHelper', 'css'], ['is_safe' => ['html']]));
        }
        return self::$twig;
    }

    /**
     * Render a template file and return the string content.
     */
    public static function render(string $view, array $data = []): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $data['csrf_token'] = $data['csrf_token'] ?? $_SESSION['csrf_token'];
        $data['app'] = $data['app'] ?? ['session' => ['csrf_token' => $_SESSION['csrf_token']]];

        $twig = self::getTwig();

        // Support extensionless view names, defaulting to .twig
        if (!str_ends_with($view, '.twig')) {
            $view .= '.twig';
        }

        try {
            return $twig->render($view, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException("Twig rendering failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Alias for render for backward compatibility.
     * @deprecated Use View::render() directly.
     */
    public static function renderToString(string $view, array $data = []): string
    {
        return self::render($view, $data);
    }
}
