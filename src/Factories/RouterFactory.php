<?php

declare(strict_types=1);

namespace Qubus\Router\Factories;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Qubus\Router\Interfaces\MiddlewareResolverInterface;
use Qubus\Router\Interfaces\RoutableInterface;
use Qubus\Router\Interfaces\RouteCollectorInterface;
use Qubus\Router\Interfaces\RouterFactoryInterface;
use Qubus\Router\Router;

final class RouterFactory implements RouterFactoryInterface
{
    protected static $routeCollector;

    protected static $container;

    protected static $responseFactory;

    protected static $resolver;

    public static function create(
        RouteCollectorInterface $routeCollector,
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolverInterface $resolver = null
    ): RoutableInterface {
        return new Router(
            $routeCollector ?? static::$routeCollector,
            $container ?? static::$container,
            $response ?? static::$responseFactory,
            $resolver ?? static::$resolver
        );
    }

    public static function setRouteCollector(RouteCollectorInterface $routeCollector): void
    {
        static::$routeCollector = $routeCollector;
    }

    public static function setContainer(ContainerInterface $container): void
    {
        static::$container = $container;
    }

    public static function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        static::$responseFactory = $responseFactory;
    }

    public static function setMiddlewareResolver(MiddlewareResolverInterface $resolver): void
    {
        static::$resolver = $resolver;
    }
}
