<?php

declare(strict_types=1);

namespace Payments\Http\Error;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

final class ErrorHandlerFactory
{
    public function __invoke(ContainerInterface $container): ErrorHandler
    {
        $handler = new ErrorHandler(
            $container->get(ResponseFactoryInterface::class),
            new JsonErrorResponseGenerator(),
        );
        $handler->attachListener(new ErrorLoggingListener($container->get(LoggerInterface::class)));
        return $handler;
    }
}
