<?php

declare(strict_types=1);

namespace Qubus\Routing\Tests\Fixtures;

use Psr\Container\ContainerInterface;
use ReflectionClass;

final class NativeObjectContainer implements ContainerInterface
{
    private ReflectionClass $reflection;

    public function __construct()
    {
        $this->reflection = new ReflectionClass(TestCallableController::class);
    }

    public function get(string $id): mixed
    {
        return new $id();
    }

    public function has(string $id): bool
    {
        return class_exists($id);
    }
}
