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
        $hasSessionCookie = isset($_COOKIE[$sessionCookieName]);
        $uri = $request->getUri();
        $isAdmin = str_starts_with($uri, '/admin_insights');
        $isPost = $request->isPost();

        // Lazy session initialization: only start if existing cookie present, admin route, or POST request
        if ($hasSessionCookie || $isAdmin || $isPost) {
            $this->sessionManager->start();
            $this->sessionManager->ensureCsrfToken();
        }

        return $next($request);
    }
}
