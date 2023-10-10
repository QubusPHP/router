<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2023
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Routing\Route;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use RuntimeException;

readonly class InjectorMiddlewareResolver implements MiddlewareResolver
{
    public function __construct(public ContainerInterface $container)
    {
    }

    /**
     * @inheritDoc
     */
    public function resolve(mixed $name): MiddlewareInterface|callable
    {
        if (is_callable($name)) {
            return $name;
        }

        try {
            $name = $this->container->get($name);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            return $e->getMessage();
        }

        if (!$name instanceof MiddlewareInterface) {
            throw new RuntimeException(
                sprintf(
                    'Middleware %s must be a callable or instance of %s.',
                    $name,
                    MiddlewareInterface::class
                )
            );
        }

        return $name;
    }
}
