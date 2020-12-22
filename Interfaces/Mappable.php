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

namespace Qubus\Routing\Interfaces;

use Qubus\Routing\Router;

interface Mappable
{
    public const HTTP_METHOD_GET     = 'GET';
    public const HTTP_METHOD_POST    = 'POST';
    public const HTTP_METHOD_PUT     = 'PUT';
    public const HTTP_METHOD_PATCH   = 'PATCH';
    public const HTTP_METHOD_OPTIONS = 'OPTIONS';
    public const HTTP_METHOD_DELETE  = 'DELETE';
    public const HTTP_METHOD_HEAD    = 'HEAD';
    public const HTTP_METHOD_TRACE   = 'TRACE';
    public const HTTP_METHOD_CONNECT = 'CONNECT';

    /**
     * Add a route to the map
     *
     * @param callable|string $callback
     * @return Route
     */
    public function map(array $verbs, string $uri, $callback): Routable;

    /**
     * Add a route that responds to any HTTP method.
     *
     * @param callable|string $callback
     * @return Route
     */
    public function any(string $uri, $callback): Routable;

    /**
     * Add a route that responds to GET HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function get(string $uri, $callback): Routable;

    /**
     * Add a route that responds to POST HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function post(string $uri, $callback): Routable;

    /**
     * Add a route that responds to PATCH HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function patch(string $uri, $callback): Routable;

    /**
     * Add a route that responds to PUT HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function put(string $uri, $callback): Routable;

    /**
     * Add a route that responds to DELETE HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function delete(string $uri, $callback): Routable;

    /**
     * Add a route that responds to HEAD HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function head(string $uri, $callback): Routable;

    /**
     * Add a route that responds to OPTIONS HTTP method
     *
     * @param callable|string $callback
     * @return Route
     */
    public function options(string $uri, $callback): Routable;

    /**
     * Add route group
     *
     * @param array|string $params
     */
    public function group($params, callable $callback): Router;
}
