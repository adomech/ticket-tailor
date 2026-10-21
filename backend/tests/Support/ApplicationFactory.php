<?php

declare(strict_types=1);

namespace Payments\Tests\Support;

use Laminas\ServiceManager\ServiceManager;
use Mezzio\Application;
use Psr\Log\LoggerInterface;

final class ApplicationFactory
{
    public static function create(LoggerInterface $logger): Application
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $container = new ServiceManager($config['dependencies']);
        $container->setService('config', $config);
        $container->setService(LoggerInterface::class, $logger);
        /** @var Application $app */
        $app = $container->get(Application::class);
        (require dirname(__DIR__, 2) . '/config/pipeline.php')($app, $container);
        (require dirname(__DIR__, 2) . '/config/routes.php')($app);
        return $app;
    }
}
