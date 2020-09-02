<?php

declare(strict_types=1);

namespace Qubus\Router\Traits;

use Qubus\Router\Interfaces\RouteInterface;

trait RouteCollectionTrait
{
    /**
     * Add a route to the map.
     *
     * @param callable|string $callback
     */
    abstract public function map(array $verbs, string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to any HTTP method.
     *
     * @param callable|string $callback
     */
    public function any(string $uri, $callback): RouteInterface
    {
        return $this->map(
            [
                static::HTTP_METHOD_GET,
                static::HTTP_METHOD_HEAD,
                static::HTTP_METHOD_POST,
                static::HTTP_METHOD_PUT,
                static::HTTP_METHOD_PATCH,
                static::HTTP_METHOD_DELETE,
                static::HTTP_METHOD_OPTIONS,
                static::HTTP_METHOD_CONNECT,
                static::HTTP_METHOD_TRACE,
            ],
            $uri,
            $callback
        );
    }

    /**
     * Add a route that responds to GET HTTP method.
     *
     * @param callable|string $callback
     */
    public function get(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_GET], $uri, $callback);
    }

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param callable|string $callback
     */
    public function post(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_POST], $uri, $callback);
    }

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param callable|string $callback
     */
    public function patch(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_PATCH], $uri, $callback);
    }

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param callable|string $callback
     */
    public function put(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_PUT], $uri, $callback);
    }

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param callable|string $callback
     */
    public function delete(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_DELETE], $uri, $callback);
    }

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param callable|string $callback
     */
    public function head(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_HEAD], $uri, $callback);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param callable|string $callback
     */
    public function options(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_OPTIONS], $uri, $callback);
    }

    /**
     * Add a route that responds to CONNECT HTTP method.
     *
     * @param callable|string $callback
     */
    public function connect(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_CONNECT], $uri, $callback);
    }

    /**
     * Add a route that responds to TRACE HTTP method.
     *
     * @param callable|string $callback
     */
    public function trace(string $uri, $callback): RouteInterface
    {
        return $this->map([static::HTTP_METHOD_TRACE], $uri, $callback);
    }
}
