<?php

declare(strict_types=1);

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * View
 * Handles presentation rendering using Twig (and legacy PHP during transition).
 */
class View
{
    private static ?Environment $twig = null;

    private static function getTwig(): Environment
    {
        if (self::$twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../Views');
            self::$twig = new \Twig\Environment($loader, [
                'cache' => false,
                'debug' => true,
            ]);
            self::$twig->addGlobal('env', $_ENV['ENVIRONMENT'] ?? 'development');
            self::$twig->addGlobal('request', \Core\Http\Request::createFromGlobals());

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            self::$twig->addGlobal('csrf_token', $_SESSION['csrf_token']);
            self::$twig->addGlobal('app', ['session' => ['csrf_token' => $_SESSION['csrf_token']]]);

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
     * Render a template file and echo it (legacy compatibility).
     */
    public static function render(string $view, array $data = []): void
    {
        echo self::renderToString($view, $data);
    }

    /**
     * Render a template file and return the string content.
     */
    public static function renderToString(string $view, array $data = []): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $twig = self::getTwig();
        $twig->addGlobal('csrf_token', $_SESSION['csrf_token']);
        $twig->addGlobal('app', ['session' => ['csrf_token' => $_SESSION['csrf_token']]]);

        // Support extensionless view names, defaulting to .twig
        if (!str_ends_with($view, '.twig') && !str_ends_with($view, '.php')) {
            $view .= '.twig';
        }

        if (str_ends_with($view, '.php')) {
            ob_start();
            self::renderPhp($view, $data);
            return ob_get_clean();
        }

        try {
            return $twig->render($view, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException("Twig rendering failed: " . $e->getMessage(), 0, $e);
        }
    }

    private static function renderPhp(string $view, array $data = []): void
    {
        extract($data);

        $path = __DIR__ . '/../Views/' . ltrim($view, '/');
        if (file_exists($path)) {
            require $path;
        } else {
            throw new \RuntimeException("View template [{$view}] not found.");
        }
    }
}
