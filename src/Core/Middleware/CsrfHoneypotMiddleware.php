<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\SessionManager;

/**
 * CsrfHoneypotMiddleware
 * Backward-compatible composite middleware piping through HoneypotMiddleware and AdminCsrfMiddleware.
 */
class CsrfHoneypotMiddleware implements MiddlewareInterface
{
    private HoneypotMiddleware $honeypotMiddleware;
    private AdminCsrfMiddleware $adminCsrfMiddleware;

    public function __construct(SessionManager $sessionManager, ?ViewRenderer $viewRenderer = null)
    {
        $this->honeypotMiddleware = new HoneypotMiddleware();
        $this->adminCsrfMiddleware = new AdminCsrfMiddleware($sessionManager, $viewRenderer);
    }

    public function process(Request $request, callable $next): Response
    {
        return $this->honeypotMiddleware->process($request, function (Request $req) use ($next) {
            return $this->adminCsrfMiddleware->process($req, $next);
        });
    }
}
