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

namespace Qubus\Routing\Factories;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Qubus\Routing\Interfaces\Collector;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;

final class RouterFactory implements RouterableFactory
{
    protected static RouteCollector $routeCollector;

    protected static ?ContainerInterface $container = null;

    protected static ?ResponseFactoryInterface $responseFactory = null;

    protected static ?MiddlewareResolver $middlewareResolver = null;

    public static function create(
        Collector $routeCollector,
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolver $middlewareResolver = null
    ): Router {
        return new Router(
            $routeCollector ?? static::$routeCollector,
            $container ?? static::$container,
            $responseFactory ?? static::$responseFactory,
            $middlewareResolver ?? static::$middlewareResolver
        );
    }

    public static function setRouteCollector(Collector $routeCollector): void
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

    public static function setMiddlewareResolver(MiddlewareResolver $middlewareResolver): void
    {
        static::$middlewareResolver = $middlewareResolver;
    }
}
