<?php

declare(strict_types=1);

namespace Controllers;

use Core\Env;
use Core\Http\Response;
use Core\ViewRenderer;

class ErrorController
{
    public static function handle404(?ViewRenderer $viewRenderer = null): Response
    {
        $html = $viewRenderer
            ? $viewRenderer->render('pages/404', [
                'page_config' => [
                    'title' => 'Page Not Found - 404',
                    'robots' => 'noindex, follow'
                ]
              ])
            : '<h1>404 Not Found</h1>';

        return Response::html($html, 404);
    }

    public static function handle500(\Throwable $e, ?ViewRenderer $viewRenderer = null): Response
    {
        error_log("Global 500 Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

        $debug = Env::get('ENVIRONMENT', 'development') === 'development';
        $errorMessage = $debug ? $e->getMessage() : 'An unexpected error occurred.';

        $html = $viewRenderer
            ? $viewRenderer->render('pages/500', [
                'error' => $errorMessage,
                'page_config' => [
                    'title' => 'Internal Server Error - 500',
                    'robots' => 'noindex, nofollow'
                ]
              ])
            : '<h1>500 Internal Server Error</h1><p>' . htmlspecialchars($errorMessage) . '</p>';

        return Response::html($html, 500);
    }
}
