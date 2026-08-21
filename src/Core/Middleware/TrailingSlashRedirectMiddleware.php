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
            $queryString = (string) $request->server('QUERY_STRING', '');
            if ($queryString !== '') {
                $canonicalUri .= '?' . $queryString;
            }
            $status = $request->isPost() ? 308 : 301;
            return Response::redirect($canonicalUri, $status);
        }

        return $next($request);
    }
}
