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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Qubus\Routing\Interfaces\Collector;
use Qubus\Routing\Interfaces\Mappable;
use Qubus\Routing\Interfaces\MiddlewareResolver;

interface RouterableFactory
{
    public static function create(
        Collector $routeCollector,
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolver $middlewareResolver = null
    ): Mappable;
}
