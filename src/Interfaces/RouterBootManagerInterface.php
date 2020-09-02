<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Qubus\Router\Http\Request;
use Qubus\Router\Router;

interface RouterBootManagerInterface
{
    /**
     * Called when router loads it's routes
     */
    public function boot(Router $router, Request $request): void;
}
