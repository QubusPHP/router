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

use Invoker\InvokerInterface;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Route\Route;

final class RouteFactory implements RoutableFactory
{
    protected static ?InvokerInterface $invoker = null;

    protected static ?MiddlewareResolver $middlewareResolver = null;

    public static function create(
        array $methods,
        string $uri,
        mixed $action,
        ?string $defaultNamespace = null,
        ?InvokerInterface $invoker = null,
        ?MiddlewareResolver $middlewareResolver = null
    ): Route {
        return new Route(
            $methods,
            $uri,
            $action,
            $defaultNamespace,
            $invoker ?? self::$invoker,
            $middlewareResolver ?? self::$middlewareResolver
        );
    }

    public static function setInvoker(InvokerInterface $invoker): void
    {
        self::$invoker = $invoker;
    }

    public static function setMiddlewareResolver(MiddlewareResolver $middlewareResolver): void
    {
        self::$middlewareResolver = $middlewareResolver;
    }
}
