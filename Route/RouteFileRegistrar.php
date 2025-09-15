<?php

declare(strict_types=1);

namespace Qubus\Routing\Route;

final class RouteFileRegistrar
{
    /**
     * Require the given routes file.
     *
     * @param string $routes
     * @return mixed
     */
    public function register(string $routes): mixed
    {
        $result = require $routes;
        return $result;
    }
}
