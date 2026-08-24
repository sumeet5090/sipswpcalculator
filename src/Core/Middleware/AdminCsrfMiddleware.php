<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Core\ViewRenderer;
use Services\SessionManagerInterface;

/**
 * AdminCsrfMiddleware
 * Dedicated single-responsibility middleware to verify CSRF tokens on administrator actions.
 */
class AdminCsrfMiddleware implements MiddlewareInterface
{
    private SessionManagerInterface $sessionManager;
    private ?ViewRenderer $viewRenderer;

    public function __construct(SessionManagerInterface $sessionManager, ?ViewRenderer $viewRenderer = null)
    {
        $this->sessionManager = $sessionManager;
        $this->viewRenderer = $viewRenderer;
    }

    public function process(Request $request, callable $next): Response
    {
        if ($request->isPost()) {
            $uri = $request->getUri();
            $isAdminPost = str_starts_with($uri, '/admin_insights');

            if ($isAdminPost) {
                $post = $request->getParsedBody();
                $token = (string) ($post['csrf_token'] ?? '');

                if ($token === '' || !$this->sessionManager->verifyCsrfToken($token)) {
                    $newToken = $this->sessionManager->generateCsrfToken();
                    if ($this->viewRenderer !== null) {
                        return Response::html($this->viewRenderer->render('admin/login', [
                            'error' => 'Security token expired or invalid. Please re-enter your password to authenticate.',
                            'csrf_token' => $newToken,
                        ]), 403, [
                            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                            'Pragma' => 'no-cache',
                        ]);
                    }
                    return new Response('Forbidden: Invalid security token. Please reload the page and try again.', 403);
                }
            }
        }

        return $next($request);
    }
}
