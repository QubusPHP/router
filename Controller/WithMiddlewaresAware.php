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

namespace Qubus\Routing\Controller;

use function is_array;

trait WithMiddlewaresAware
{
    /**
     * List of controller middleware.
     *
     * @var array $middlewares
     */
    protected array $middlewares = [];

    /**
     * Add Middleware.
     */
    public function middleware(mixed $middleware): ControllerMiddlewareOptions
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }

        $options = new ControllerMiddlewareOptions();

        foreach ($middleware as $m) {
            $this->middlewares[] = new ControllerMiddlewarePipe($m, $options);
        }

        return $options;
    }

    /**
     * Get the array of controller middleware.
     */
    public function getControllerMiddleware(): array
    {
        return $this->middlewares;
    }
}
