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
use Qubus\Exception\Data\TypeException;
use Qubus\Routing\Interfaces\MiddlewareResolver;

use function array_map;
use function array_pad;
use function array_values;
use function explode;
use function get_debug_type;
use function is_callable;
use function method_exists;
use function sprintf;
use function str_contains;
use function trim;

class InjectorMiddlewareResolver implements MiddlewareResolver
{
    public function __construct(public ContainerInterface $container)
    {
    }

    /**
     * @inheritDoc
     * @param mixed $definition
     * @return MiddlewareInterface|callable
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws TypeException
     */
    public function resolve(mixed $definition): MiddlewareInterface|callable
    {
        // Case 1: The middleware is a callable.
        if (is_callable($definition)) {
            return $definition;
        }

        // Case 2: Already a PSR-15 middleware instance.
        if ($definition instanceof MiddlewareInterface) {
            return $definition;
        }

        // Case 3: Class name string (non-aliased).
        if (is_string($definition) && class_exists($definition)) {
            $middleware = new $definition();
            if (!$middleware instanceof MiddlewareInterface) {
                throw new TypeException(
                    sprintf('Class "%s" must implement %s.', $definition, MiddlewareInterface::class)
                );
            }
            return $middleware;
        }

        // Case 4: Alias string (with optional args).
        if (is_string($definition)) {
            [$alias, $argString] = array_pad(
                array: explode(separator: ':', string: $definition, limit: 2),
                length: 2,
                value: null
            );
            $alias = trim(string: $alias);

            // Attempt to fetch from container
            if (!$this->container->has($alias)) {
                throw new TypeException(sprintf(
                    'Middleware alias "%s" not found in container.',
                    $alias
                ));
            }

            $middleware = $this->container->get($alias);

            if (!$middleware instanceof MiddlewareInterface) {
                throw new TypeException(sprintf(
                    'Middleware "%s" must implement %s.',
                    $alias,
                    MiddlewareInterface::class
                ));
            }

            if ($argString !== null) {
                [$args, $options] = $this->parseArguments($argString);

                if (method_exists(object_or_class: $middleware, method: 'withOptions')) {
                    $middleware = $middleware->withOptions($options ?: $args);
                } elseif (method_exists(object_or_class: $middleware, method: 'withArguments')) {
                    $middleware = $middleware->withArguments(...array_values($args));
                }
            }

            return $middleware;
        }

        throw new TypeException(sprintf(
            'Invalid middleware definition: %s',
            get_debug_type($definition)
        ));
    }

    /**
     * Parses arguments into 2 different formats:
     *
     *  - positional: ['manage:users', '/no-access']
     *  - key-value:  ['permission' => 'manage:users', 'redirect' => '/no-access']
     *
     * @return array{0: array<int, string>, 1: array<string, string>}
     */
    protected function parseArguments(string $argString): array
    {
        $pairs = array_map(callback: 'trim', array: explode(separator: ',', string: $argString));
        $args = [];
        $options = [];

        foreach ($pairs as $pair) {
            if ($pair === '') {
                continue;
            }

            if (str_contains($pair, '=')) {
                [$key, $value] = explode(separator: '=', string: $pair, limit: 2);
                $key = trim(string: $key);
                $value = trim(string: $value);

                $args[] = $value;        // for positional support
                $options[$key] = $value; // for named support
            } else {
                $value = trim(string: $pair);
                $args[] = $value;
            }
        }

        return [$args, $options];
    }
}
