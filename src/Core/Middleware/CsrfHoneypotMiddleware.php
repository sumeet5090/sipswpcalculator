<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Services\SessionManager;

class CsrfHoneypotMiddleware implements MiddlewareInterface
{
    private SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    public function process(Request $request, callable $next): Response
    {
        if ($request->isPost()) {
            $post = $request->getParsedBody();

            if (!empty($post['website_url'])) {
                return new Response('Forbidden: Automated request detected.', 403);
            }

            if (array_key_exists('csrf_token', $post)) {
                $token = (string) $post['csrf_token'];
                if (!$this->sessionManager->verifyCsrfToken($token)) {
                    return new Response('Forbidden: Invalid security token. Please reload the page and try again.', 403);
                }
            }
        }

        return $next($request);
    }
}
