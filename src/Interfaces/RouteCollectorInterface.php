<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

interface RouteCollectorInterface
{
    /**
     * Get route objects.
     */
    public function getRoutes(): array;

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

    /**
     * Set the base path.
     *
     * Useful if you are running your application from a subdirectory.
     */
    public function setBasePath(string $basePath): void;

    /**
     * Set the domain.
     *
     * Useful for api routing.
     */
    public function setDomain(string $domain): void;

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
    public function map(string $method, string $domain, string $route, $target, ?string $name = null);

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
    public function generateUri(string $routeName, array $params = []);

    /**
     * Match a given Request Url against stored routes
     *
     * @return array|bool Array with route information on success, false on failure (no match).
     */
    public function match(?string $requestHost = null, ?string $requestUrl = null, ?string $requestMethod = null);

    /**
     *  Adds a path at the beginning of a url.
     *
     * Useful if you are running your application from a subdirectory.
     */
    public function prependUrl(string $basePath): void;
}
