<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Invoker\InvokerInterface;

interface RouteFactoryInterface
{
    public static function create(
        array $methods,
        string $uri,
        $action,
        ?string $defaultNamespace = null,
        ?InvokerInterface $invoker = null,
        ?MiddlewareResolverInterface $resolver = null
    ): RouteInterface;
}
