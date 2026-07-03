<?php

declare(strict_types=1);

namespace Core;

/**
 * View
 * Handles presentation rendering under isolated variable scopes (Template View pattern).
 */
class View
{
    /**
     * Render a template file within an isolated scope.
     *
     * @param string $view The view path relative to src/Views/ (e.g. 'pages/about')
     * @param array $data Associative array of variables to inject into template scope
     */
    public static function render(string $view, array $data = []): void
    {
        extract($data);

        $path = __DIR__ . '/../Views/' . ltrim($view, '/') . '.php';
        if (file_exists($path)) {
            require $path;
        } else {
            http_response_code(500);
            echo "500 Internal Server Error: View template [{$view}] not found.";
            exit;
        }
    }
}
