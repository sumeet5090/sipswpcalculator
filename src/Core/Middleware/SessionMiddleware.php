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
        $sessionCookieName = session_name();
        $hasSessionCookie = $request->getCookie($sessionCookieName) !== null;
        $uri = $request->getUri();
        $isAdmin = str_starts_with($uri, '/admin_insights');
        // Lazy session initialization: only start if existing cookie present or admin route
        if ($hasSessionCookie || $isAdmin) {
            $this->sessionManager->start();
            $this->sessionManager->ensureCsrfToken();
        }

        return $next($request);
    }
}
