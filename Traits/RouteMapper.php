<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Traits;

use Qubus\Routing\Interfaces\Routable;

trait RouteMapper
{
    /**
     * Add a route to the map.
     *
     * @param callable|string $callback
     */
    abstract public function map(array $verbs, string $uri, $callback): Routable;

    /**
     * Add a route that responds to any HTTP method.
     *
     * @param callable|string $callback
     */
    public function any(string $uri, $callback): Routable
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
    public function get(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_GET], $uri, $callback);
    }

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @param callable|string $callback
     */
    public function post(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_POST], $uri, $callback);
    }

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @param callable|string $callback
     */
    public function patch(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_PATCH], $uri, $callback);
    }

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @param callable|string $callback
     */
    public function put(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_PUT], $uri, $callback);
    }

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @param callable|string $callback
     */
    public function delete(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_DELETE], $uri, $callback);
    }

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @param callable|string $callback
     */
    public function head(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_HEAD], $uri, $callback);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @param callable|string $callback
     */
    public function options(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_OPTIONS], $uri, $callback);
    }

    /**
     * Add a route that responds to CONNECT HTTP method.
     *
     * @param callable|string $callback
     */
    public function connect(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_CONNECT], $uri, $callback);
    }

    /**
     * Add a route that responds to TRACE HTTP method.
     *
     * @param callable|string $callback
     */
    public function trace(string $uri, $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_TRACE], $uri, $callback);
    }
}
