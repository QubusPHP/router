<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Qubus\Router\Router;

interface EventHandlerInterface
{
    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
     */
    public function getEvents(?string $name): array;

    /**
     * Fires any events registered with given event-name
     *
     * @param Router $router Router instance
     * @param string $name Event name
     * @param array  $eventArgs Event arguments
     */
    public function fireEvents(Router $router, string $name, array $eventArgs = []): void;
}
