<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Qubus\Router\Router;

interface RoutableInterface
{
    const HTTP_METHOD_GET     = 'GET';
    const HTTP_METHOD_POST    = 'POST';
    const HTTP_METHOD_PUT     = 'PUT';
    const HTTP_METHOD_PATCH   = 'PATCH';
    const HTTP_METHOD_OPTIONS = 'OPTIONS';
    const HTTP_METHOD_DELETE  = 'DELETE';
    const HTTP_METHOD_HEAD    = 'HEAD';
    const HTTP_METHOD_TRACE   = 'TRACE';
    const HTTP_METHOD_CONNECT = 'CONNECT';

    /**
     * Add a route to the map
     *
     * @param callable|string $callback
     * @return Route
     */
    public function map(array $verbs, string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to any HTTP method.
     *
     * @param callable|string $callback
     * @return Route
     */
    public function any(string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to GET HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function get(string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to POST HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function post(string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to PATCH HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function patch(string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to PUT HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function put(string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to DELETE HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function delete(string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to HEAD HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function head(string $uri, $callback): RouteInterface;

    /**
     * Add a route that responds to OPTIONS HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function options(string $uri, $callback): RouteInterface;

    /**
     * Add route group
     *
     * @param array|string   $params
     * @param callable $callback
     */
    public function group($params, callable $callback): Router;
}
