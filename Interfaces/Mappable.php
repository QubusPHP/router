<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Routing\Interfaces;

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
     * @param array $verbs
     * @param string $uri
     * @param callable|string $callback
     * @return Routable
     */
    public function map(array $verbs, string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to any HTTP method.
     */
    public function any(string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to GET HTTP method
     */
    public function get(string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to POST HTTP method
     */
    public function post(string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to PATCH HTTP method
     */
    public function patch(string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to PUT HTTP method
     */
    public function put(string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to DELETE HTTP method
     */
    public function delete(string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to HEAD HTTP method
     */
    public function head(string $uri, callable|string $callback): Routable;

    /**
     * Add a route that responds to OPTIONS HTTP method
     */
    public function options(string $uri, callable|string $callback): Routable;

    /**
     * Add route group
     */
    public function group(array|string $params, callable $callback): self;
}
