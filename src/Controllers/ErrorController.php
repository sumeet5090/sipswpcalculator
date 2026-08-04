<?php

declare(strict_types=1);

namespace Controllers;

use Core\Env;
use Core\Http\Response;
use Core\View;

class ErrorController
{
    public static function handle404(): Response
    {
        $response = Response::html(View::render('pages/404', [
            'page_config' => [
                'title' => 'Page Not Found - 404',
                'robots' => 'noindex, follow'
            ]
        ]), 404);

        return $response;
    }

    public static function handle500(\Throwable $e): Response
    {
        error_log("Global 500 Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

        $debug = Env::get('ENVIRONMENT', 'development') === 'development';

        $response = Response::html(View::render('pages/500', [
            'error' => $debug ? $e->getMessage() : 'An unexpected error occurred.',
            'page_config' => [
                'title' => 'Internal Server Error - 500',
                'robots' => 'noindex, nofollow'
            ]
        ]), 500);

        return $response;
    }
}
