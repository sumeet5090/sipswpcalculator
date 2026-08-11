<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Services\SessionManager;

class SessionMiddleware implements MiddlewareInterface
{
    private SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function process(Request $request, callable $next): Response
    {
        $this->sessionManager->start();
        $this->sessionManager->ensureCsrfToken();
        return $next($request);
    }
}
