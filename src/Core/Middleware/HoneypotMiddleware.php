<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;

/**
 * HoneypotMiddleware
 * Dedicated single-responsibility middleware to detect and reject automated bot submissions.
 */
class HoneypotMiddleware implements MiddlewareInterface
{
    private string $fieldName;

    public function __construct(string $fieldName = 'website_url')
    {
        $this->fieldName = $fieldName;
    }

    public function process(Request $request, callable $next): Response
    {
        if ($request->isPost()) {
            $post = $request->getParsedBody();

            if (isset($post[$this->fieldName]) && (!is_string($post[$this->fieldName]) || trim((string) $post[$this->fieldName]) !== '')) {
                return new Response('Forbidden: Automated request detected.', 403);
            }
        }

        return $next($request);
    }
}
