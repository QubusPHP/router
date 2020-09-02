<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

interface MiddlewareResolverInterface
{
    /**
     * Resolves a middleware
     *
     * @param  mixed $name The key to lookup a middleware
     * @return mixed
     */
    public function resolve($name);
}
