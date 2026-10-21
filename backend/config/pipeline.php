<?php

declare(strict_types=1);

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Payments\Http\Error\JsonNotFoundHandler;
use Payments\Http\Middleware\JsonMethodNotAllowedMiddleware;
use Psr\Container\ContainerInterface;

return static function (Application $app, ContainerInterface $container): void {
    $app->pipe($container->get(ErrorHandler::class));
    $app->pipe($container->get(RouteMiddleware::class));
    $app->pipe($container->get(JsonMethodNotAllowedMiddleware::class));
    $app->pipe($container->get(DispatchMiddleware::class));
    $app->pipe($container->get(JsonNotFoundHandler::class));
};
