<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Traits;

use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Interfaces\Routable;

trait RouteMapper
{
    /**
     * Add a route to the map.
     */
    abstract public function map(array $verbs, string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to any HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function any(string $uri, callable|string $callback): Routable
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
     * @throws TooLateToAddNewRouteException
     */
    public function get(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_GET], $uri, $callback);
    }

    /**
     * Add a route that responds to POST HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function post(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_POST], $uri, $callback);
    }

    /**
     * Add a route that responds to PATCH HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function patch(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_PATCH], $uri, $callback);
    }

    /**
     * Add a route that responds to PUT HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function put(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_PUT], $uri, $callback);
    }

    /**
     * Add a route that responds to DELETE HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function delete(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_DELETE], $uri, $callback);
    }

    /**
     * Add a route that responds to HEAD HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function head(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_HEAD], $uri, $callback);
    }

    /**
     * Add a route that responds to OPTIONS HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function options(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_OPTIONS], $uri, $callback);
    }

    /**
     * Add a route that responds to CONNECT HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function connect(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_CONNECT], $uri, $callback);
    }

    /**
     * Add a route that responds to TRACE HTTP method.
     *
     * @throws TooLateToAddNewRouteException
     */
    public function trace(string $uri, callable|string $callback): Routable
    {
        return $this->map([static::HTTP_METHOD_TRACE], $uri, $callback);
    }
}
