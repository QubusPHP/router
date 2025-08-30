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

use RuntimeException;

interface Collector
{
    public array $routes { get; }

    /**
     * Add multiple routes at once from array using the following format:
     *
     *   $routes = [
     *      [$method, $route, $target, $name]
     *   ];
     *
     * @throws RuntimeException
     */
    public function addRoutes(array $routes): void;

    public string $basePath { set; }

    public string $domain { set; }

    /**
     * Map a route to a target
     *
     * @param string      $method One of 5 HTTP Methods, or a pipe-separated list
     *                            of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param string      $route The route regex, custom regex must start with an @.
     *                            You can use multiple pre-set regex filters, like [i:id]
     * @param mixed       $target The target where this route should point to. Can be anything.
     * @param null|string $name Optional name of this route. Supply if you want to
     *                     reverse route this url in your application.
     * @throws RuntimeException
     */
    public function map(string $method, string $domain, string $route, mixed $target, ?string $name = null): void;

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName The name of the route.
     * @param array  $params     Associative array of parameters to replace placeholders with.
     * @return string The URL of the route with named parameters in place.
     * @throws RuntimeException
     */
    public function generateUri(string $routeName, array $params = []): string;

    /**
     * Match a given Request Url against stored routes
     *
     * @return array|bool Array with route information on success, false on failure (no match).
     */
    public function match(
        ?string $requestHost = null,
        ?string $requestUrl = null,
        ?string $requestMethod = null
    ): bool|array;

    /**
     *  Adds a path at the beginning of a url.
     *
     * Useful if you are running your application from a subdirectory.
     */
    public function prependUrl(string $basePath): void;
}
