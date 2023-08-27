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

interface ControllerMiddlewareDelegate
{
    public function middleware(mixed $middleware): ControllerMiddlewareOptions;

    public function getControllerMiddleware(): array;
}
