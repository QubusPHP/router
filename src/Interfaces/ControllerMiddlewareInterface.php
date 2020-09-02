<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Qubus\Router\ControllerMiddlewareOptions;

interface ControllerMiddlewareInterface
{
    public function middleware($middleware): ControllerMiddlewareOptions;

    public function getControllerMiddleware(): array;
}
