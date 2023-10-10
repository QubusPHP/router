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
        ContainerInterface $container,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolver $middlewareResolver = null
    ): Router {
        return new Router(
            $routeCollector ?? self::$routeCollector,
            $container ?? self::$container,
            $responseFactory ?? self::$responseFactory,
            $middlewareResolver ?? self::$middlewareResolver
        );
    }

    public static function setRouteCollector(Collector $routeCollector): void
    {
        self::$routeCollector = $routeCollector;
    }

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        self::$responseFactory = $responseFactory;
    }

    public static function setMiddlewareResolver(MiddlewareResolver $middlewareResolver): void
    {
        self::$middlewareResolver = $middlewareResolver;
    }
}
