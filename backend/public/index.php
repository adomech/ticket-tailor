<?php

declare(strict_types=1);

use Mezzio\Application;
use Psr\Container\ContainerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var ContainerInterface $container */
$container = require dirname(__DIR__) . '/config/container.php';
/** @var Application $app */
$app = $container->get(Application::class);
(require dirname(__DIR__) . '/config/pipeline.php')($app, $container);
(require dirname(__DIR__) . '/config/routes.php')($app);
$app->run();
