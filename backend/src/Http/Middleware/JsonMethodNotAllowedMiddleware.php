<?php

declare(strict_types=1);

namespace Payments\Http\Middleware;

use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonMethodNotAllowedMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $request->getAttribute(RouteResult::class);
        if (!$result instanceof RouteResult || !$result->isMethodFailure()) {
            return $handler->handle($request);
        }
        $methods = $result->getAllowedMethods();
        return new JsonResponse(
            ['message' => 'Method not allowed.', 'reference' => bin2hex(random_bytes(8))],
            405,
            ['Allow' => implode(',', is_array($methods) ? $methods : []), 'Cache-Control' => 'no-store'],
        );
    }
}
