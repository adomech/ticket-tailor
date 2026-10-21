<?php

declare(strict_types=1);

use Mezzio\Application;
use Payments\Http\Handler\DashboardHandler;

return static function (Application $app): void {
    $app->get('/api/dashboard', DashboardHandler::class, 'dashboard');
};
