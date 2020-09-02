<?php

declare(strict_types=1);

namespace Qubus\Router\Traits;

use Qubus\Router\ControllerMiddleware;
use Qubus\Router\ControllerMiddlewareOptions;

use function is_array;

trait ControllerMiddlewareTrait
{
    /**
     * List of ControllerMiddleware
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Add Middleware
     *
     * @param  Psr\Http\Server\MiddlewareInterface|array $middleware
     * @return Qubus\Router\ControllerMiddlewareOptions
     */
    public function middleware($middleware): ControllerMiddlewareOptions
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }

        $options = new ControllerMiddlewareOptions();

        foreach ($middleware as $m) {
            $this->middlewares[] = new ControllerMiddleware($m, $options);
        }

        return $options;
    }

    /**
     * Get the array of ControllerMiddleware
     */
    public function getControllerMiddleware(): array
    {
        return $this->middlewares;
    }
}
