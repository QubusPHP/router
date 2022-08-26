<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/routing
 * @copyright  2020
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Controller;

use function in_array;
use function is_array;

final class ControllerMiddlewareOptions
{
    /** @var array $only */
    protected array $only = [];

    /** @var array $except */
    protected array $except = [];

    /**
     * Specify the methods that the middleware applies to
     *
     * @return $this
     */
    public function only(array|string $method): self
    {
        if (! is_array($method)) {
            $method = [$method];
        }

        $this->only += $method;

        return $this;
    }

    /**
     * Specify the methods that the middleware does not apply to.
     *
     * @return $this
     */
    public function except(array|string $method): self
    {
        if (! is_array($method)) {
            $method = [$method];
        }

        $this->except += $method;

        return $this;
    }

    /**
     * Is a specific method excluded by the options set on this object.
     */
    public function excludedForMethod(string $method): bool
    {
        if (empty($this->only) && empty($this->except)) {
            return false;
        }

        return (! empty($this->only) && ! in_array($method, $this->only)) ||
        (! empty($this->except) && in_array($method, $this->except));
    }
}
