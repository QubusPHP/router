<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Controller;

use Psr\Http\Server\MiddlewareInterface;

final class ControllerMiddlewarePipe
{
    protected array|MiddlewareInterface $middleware;

    protected ControllerMiddlewareOptions $options;

    /**
     * Constructor
     */
    public function __construct(MiddlewareInterface|array $middleware, ControllerMiddlewareOptions $options)
    {
        $this->middleware = $middleware;
        $this->options    = $options;
    }

    /**
     * Get the Middleware.
     */
    public function middleware(): MiddlewareInterface|array|string
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
     */
    public function excludedForMethod(string $method): bool
    {
        return $this->options->excludedForMethod($method);
    }
}
