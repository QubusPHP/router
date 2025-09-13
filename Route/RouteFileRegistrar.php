<?php

declare(strict_types=1);

namespace Qubus\Routing\Route;

use Qubus\Routing\Psr7Router;

final class RouteFileRegistrar
{
    /**
     * The router instance.
     */
    protected ?Psr7Router $router = null;

    /**
     * Create a new route file registrar instance.
     *
     * @param Psr7Router $router
     */
    public function __construct(Psr7Router $router)
    {
        $this->router = $router;
    }

    /**
     * Require the given routes file.
     *
     * @param string $routes
     * @return void
     */
    public function register(string $routes): void
    {
        $router = $this->router;

        require $routes;
    }
}
