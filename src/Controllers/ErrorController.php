<?php

declare(strict_types=1);

namespace Controllers;

use Core\Env;
use Core\Http\Response;
use Core\ViewRenderer;

class ErrorController
{
    private ?ViewRenderer $viewRenderer;
    private string $environment;

    public function __construct(?ViewRenderer $viewRenderer = null, string $environment = 'production')
    {
        $this->viewRenderer = $viewRenderer;
        $this->environment = $environment;
    }

    public function render404(): Response
    {
        $html = $this->viewRenderer
            ? $this->viewRenderer->render('pages/404', [
                'page_config' => [
                    'title' => 'Page Not Found - 404',
                    'meta_desc' => 'The requested page could not be found. Return to SIP & SWP Calculator to plan your investments.',
                    'canonical' => '',
                    'robots' => 'noindex, follow'
                ]
              ])
            : '<h1>404 Not Found</h1>';

        return Response::html($html, 404);
    }

    public function render500(\Throwable $e): Response
    {
        error_log("Global 500 Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\nStack Trace:\n" . $e->getTraceAsString());

        $isDebug = ($this->environment === 'development');
        $errorMessage = $isDebug ? $e->getMessage() : 'An unexpected error occurred.';

        $html = $this->viewRenderer
            ? $this->viewRenderer->render('pages/500', [
                'error' => $errorMessage,
                'page_config' => [
                    'title' => 'Internal Server Error - 500',
                    'meta_desc' => 'An internal server error occurred while processing your calculation request.',
                    'canonical' => '',
                    'robots' => 'noindex, nofollow'
                ]
              ])
            : '<h1>500 Internal Server Error</h1><p>' . htmlspecialchars($errorMessage) . '</p>';

        return Response::html($html, 500);
    }

    public static function handle404(?ViewRenderer $viewRenderer = null): Response
    {
        $html = $viewRenderer
            ? $viewRenderer->render('pages/404', [
                'page_config' => [
                    'title' => 'Page Not Found - 404',
                    'meta_desc' => 'The requested page could not be found. Return to SIP & SWP Calculator to plan your investments.',
                    'canonical' => '',
                    'robots' => 'noindex, follow'
                ]
              ])
            : '<h1>404 Not Found</h1>';

        return Response::html($html, 404);
    }

    /**
     * @param \Throwable $e
     * @param ViewRenderer|null $viewRenderer
     * @return Response
     *
     * NOTE: Env::get('ENVIRONMENT') is intentionally called here.
     * This method serves as the last-resort exception handler, invoked before or
     * after DI resolution fails. Injecting $env via constructor is not possible
     * for a static fallback handler. This is a documented architectural exception.
     */
    public static function handle500(\Throwable $e, ?ViewRenderer $viewRenderer = null): Response
    {
        error_log("Global 500 Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\nStack Trace:\n" . $e->getTraceAsString());

        $isDebug = (Env::get('ENVIRONMENT', 'production') === 'development');
        $errorMessage = $isDebug ? $e->getMessage() : 'An unexpected error occurred.';

        $html = $viewRenderer
            ? $viewRenderer->render('pages/500', [
                'error' => $errorMessage,
                'page_config' => [
                    'title' => 'Internal Server Error - 500',
                    'meta_desc' => 'An internal server error occurred while processing your calculation request.',
                    'canonical' => '',
                    'robots' => 'noindex, nofollow'
                ]
              ])
            : '<h1>500 Internal Server Error</h1><p>' . htmlspecialchars($errorMessage) . '</p>';

        return Response::html($html, 500);
    }
}
