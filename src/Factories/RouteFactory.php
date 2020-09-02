<?php

declare(strict_types=1);

namespace Qubus\Router\Factories;

use Invoker\InvokerInterface;
use Qubus\Router\Interfaces\MiddlewareResolverInterface;
use Qubus\Router\Interfaces\RouteFactoryInterface;
use Qubus\Router\Interfaces\RouteInterface;
use Qubus\Router\Route\Route;

final class RouteFactory implements RouteFactoryInterface
{
    protected static $invoker;

    protected static $resolver;

    public static function create(
        array $methods,
        string $uri,
        $action,
        ?string $defaultNamespace = null,
        ?InvokerInterface $invoker = null,
        ?MiddlewareResolverInterface $resolver = null
    ): RouteInterface {
        return new Route(
            $methods,
            $uri,
            $action,
            $defaultNamespace,
            $invoker ?? static::$invoker,
            $resolver ?? static::$resolver
        );
    }

    public static function setInvoker(InvokerInterface $invoker): void
    {
        static::$invoker = $invoker;
    }

    public static function setMiddlewareResolver(MiddlewareResolverInterface $resolver): void
    {
        static::$resolver = $resolver;
    }
}
