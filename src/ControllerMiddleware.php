<?php

declare(strict_types=1);

namespace Qubus\Router;

final class ControllerMiddleware
{
    /** @var Psr\Http\Server\MiddlewareInterface */
    protected $middleware;

    /** @var Qubus\Router\ControllerMiddlewareOptions */
    protected $options;

    /**
     * Constructor
     *
     * @param Psr\Http\Server\MiddlewareInterface|array $middleware
     * @param Qubus\Router\ControllerMiddlewareOptions  $options
     */
    public function __construct($middleware, ControllerMiddlewareOptions $options)
    {
        $this->middleware = $middleware;
        $this->options    = $options;
    }

    /**
     * Get the Middleware
     *
     * @return Psr\Http\Server\MiddlewareInterface|string
     */
    public function middleware()
    {
        return $this->middleware;
    }

    /**
     * Get the ControllerMiddlewareOptions
     *
     * @return Qubus\Router\ControllerMiddlewareOptions
     */
    public function options(): ControllerMiddlewareOptions
    {
        return $this->options;
    }

    /**
     * Is a specific method excluded by the options set on this object
     *
     * @param  string $method
     */
    public function excludedForMethod($method): bool
    {
        return $this->options->excludedForMethod($method);
    }
}
