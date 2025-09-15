<?php

declare(strict_types=1);

namespace Qubus\Routing\Route;

use JsonException;
use Qubus\Exception\Data\TypeException;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Router;
use RuntimeException;

use function is_callable;
use function is_string;
use function pathinfo;
use function realpath;
use function sprintf;

use const PATHINFO_EXTENSION;

final class RoutingRegistrar
{
    public function __construct(private Router $router)
    {
    }

    /**
     * @throws JsonException
     * @throws TooLateToAddNewRouteException
     * @throws TypeException
     */
    public function load(array|string|callable $sources): void
    {
        foreach ((array) $sources as $source) {
            $this->loadOne($source);
        }
    }

    public function group(array|string|callable $sources, array $middleware = [], string $prefix = ''): void
    {
        $router = $this->router;

        $router->group(['middleware' => $middleware, 'prefix' => $prefix], function () use ($sources) {
            $this->load($sources);
        });
    }

    /**
     * @throws TooLateToAddNewRouteException
     * @throws JsonException
     * @throws TypeException
     */
    private function loadOne(string|callable $source): void
    {
        if (is_callable($source)) {
            $source($this->router);
            return;
        }

        if (is_string($source) && realpath($source)) {
            $ext = pathinfo($source, PATHINFO_EXTENSION);

            if ($ext === 'php') {
                $result = new RouteFileRegistrar()->register($source);
                if (is_callable($result)) {
                    $result($this->router);
                }
                return;
            }

            if ($ext === 'json') {
                $this->router->loadRoutesFromJson($source);
                return;
            }
        }

        throw new RuntimeException(sprintf("Unsupported route source: %s", $source));
    }
}
