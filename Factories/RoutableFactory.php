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

namespace Qubus\Routing\Factories;

use Invoker\InvokerInterface;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Route\Route;

interface RoutableFactory
{
    public static function create(
        array $methods,
        string $uri,
        mixed $action,
        ?string $defaultNamespace = null,
        ?InvokerInterface $invoker = null,
        ?MiddlewareResolver $middlewareResolver = null
    ): Route;
}
