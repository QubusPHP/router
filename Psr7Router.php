<?php

declare(strict_types=1);

namespace Qubus\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Interfaces\Routable;

interface Psr7Router
{
    /**
     * Match a given Request Url against stored routes
     * converted into a Response.
     *
     * @param ServerRequestInterface $serverRequest
     * @return ResponseInterface
     */
    public function match(ServerRequestInterface $serverRequest): ResponseInterface;

    /**
     * Check if a route exists based on its name.
     *
     * @param  string $name The name of the route.
     * @return bool True if the named routed exists, false otherwise.
     */
    public function has(string $name): bool;

    /**
     * Generate url's from named routes.
     *
     * @param  string $name   Name of the route.
     * @param  array  $params Data parameters.
     * @return string The url.
     * @throws RouteParamFailedConstraintException
     * @throws NamedRouteNotFoundException
     */
    public function url(string $name, array $params = []): string;

    /**
     * Redirect one route to another.
     *
     * @param string $from Originating route.
     * @param string $to Destination route.
     * @param int $status HTTP status code.
     * @return Routable
     * @throws TooLateToAddNewRouteException
     */
    public function redirect(string $from, string $to, int $status = 302): Routable;
}
