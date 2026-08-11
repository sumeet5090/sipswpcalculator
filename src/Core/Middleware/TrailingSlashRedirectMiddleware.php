<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Http\Request;
use Core\Http\Response;

class TrailingSlashRedirectMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $uri = $request->getUri();

        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $canonicalUri = rtrim($uri, '/');
            return Response::redirect($canonicalUri, 301);
        }

        return $next($request);
    }
}
