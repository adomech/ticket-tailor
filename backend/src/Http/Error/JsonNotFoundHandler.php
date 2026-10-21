<?php

declare(strict_types=1);

namespace Payments\Http\Error;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonNotFoundHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(
            ['message' => 'Not found.', 'reference' => bin2hex(random_bytes(8))],
            404,
            ['Cache-Control' => 'no-store'],
        );
    }
}
