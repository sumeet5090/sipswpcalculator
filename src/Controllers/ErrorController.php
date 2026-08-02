<?php

declare(strict_types=1);

namespace Controllers;

use Core\View;

class ErrorController
{
    public static function handle404(): void
    {
        http_response_code(404);
        View::render('pages/404', [
            'page_config' => [
                'title' => 'Page Not Found - 404',
                'robots' => 'noindex, follow'
            ]
        ]);
        exit;
    }

    public static function handle500(\Throwable $e): void
    {
        http_response_code(500);
        error_log("Global 500 Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

        $debug = ($_ENV['ENVIRONMENT'] ?? 'development') === 'development';

        View::render('pages/500', [
            'error' => $debug ? $e->getMessage() : 'An unexpected error occurred.',
            'page_config' => [
                'title' => 'Internal Server Error - 500',
                'robots' => 'noindex, nofollow'
            ]
        ]);
        exit;
    }
}
