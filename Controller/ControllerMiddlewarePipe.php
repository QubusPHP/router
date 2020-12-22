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

final class ControllerMiddlewarePipe
{
    /** @var MiddlewareInterface|array $middleware */
    protected $middleware;

    protected ControllerMiddlewareOptions $options;

    /**
     * Constructor
     *
     * @param MiddlewareInterface|array $middleware
     */
    public function __construct($middleware, ControllerMiddlewareOptions $options)
    {
        $this->middleware = $middleware;
        $this->options    = $options;
    }

    /**
     * Get the Middleware.
     *
     * @return MiddlewareInterface|string
     */
    public function middleware()
    {
        return $this->middleware;
    }

    /**
     * Get the ControllerMiddlewareOptions.
     */
    public function options(): ControllerMiddlewareOptions
    {
        return $this->options;
    }

    /**
     * Is a specific method excluded by the options set on this object.
     *
     * @param string $method
     */
    public function excludedForMethod($method): bool
    {
        return $this->options->excludedForMethod($method);
    }
}
