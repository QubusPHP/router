<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

interface RouterFactoryInterface
{
    public static function create(
        RouteCollectorInterface $routeCollector,
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolverInterface $resolver = null
    ): RoutableInterface;
}
