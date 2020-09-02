<?php

declare(strict_types=1);

namespace Qubus\Router;

use function in_array;
use function is_array;

final class ControllerMiddlewareOptions
{
    /** @var array */
    protected $only = [];

    /** @var array */
    protected $except = [];

    /**
     * Specify the methods that the middleware applies to
     *
     * @param  string|array $method
     * @return Qubus\Router\ControllerMiddlewareOptions
     */
    public function only($method): self
    {
        if (! is_array($method)) {
            $method = [$method];
        }

        $this->only += $method;

        return $this;
    }

    /**
     * Specify the methods that the middleware does not apply to
     *
     * @param  string|array $method
     * @return Qubus\Router\ControllerMiddlewareOptions
     */
    public function except($method): self
    {
        if (! is_array($method)) {
            $method = [$method];
        }

        $this->except += $method;

        return $this;
    }

    /**
     * Is a specific method excluded by the options set on this object
     *
     * @param  string $method
     */
    public function excludedForMethod($method): bool
    {
        if (empty($this->only) && empty($this->except)) {
            return false;
        }

        return (! empty($this->only) && ! in_array($method, $this->only)) ||
            (! empty($this->except) && in_array($method, $this->except));
    }
}
