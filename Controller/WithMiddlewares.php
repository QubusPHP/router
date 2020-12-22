<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Controller;

use Psr\Http\Server\MiddlewareInterface;

use function is_array;

trait WithMiddlewares
{
    /**
     * List of controller middleware.
     *
     * @var array $middlewares
     */
    protected array $middlewares = [];

    /**
     * Add Middleware.
     *
     * @param MiddlewareInterface|array $middleware
     */
    public function middleware($middleware): ControllerMiddlewareOptions
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
