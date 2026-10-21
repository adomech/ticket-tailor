<?php

declare(strict_types=1);

namespace Payments\Http\Error;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JsonErrorResponseGenerator
{
    public function __invoke(
        \Throwable $error,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $reference = bin2hex(random_bytes(8));
        return new JsonResponse(
            ['message' => 'An unexpected error occurred.', 'reference' => $reference],
            500,
            ['Cache-Control' => 'no-store', 'X-Request-Reference' => $reference],
        );
    }
}
