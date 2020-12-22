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

namespace Qubus\Routing\Factories;

use Invoker\InvokerInterface;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Interfaces\Routable;

interface RoutableFactory
{
    public static function create(
        array $methods,
        string $uri,
        $action,
        ?string $defaultNamespace = null,
        ?InvokerInterface $invoker = null,
        ?MiddlewareResolver $middlewareResolver = null
    ): Routable;
}
