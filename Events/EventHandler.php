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

namespace Qubus\Routing\Events;

use Qubus\Routing\Router;

interface EventHandler
{
    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
     */
    public function getEvents(?string $name): array;

    /**
     * Fires any events registered with given event-name.
     *
     * @param Router $router Router instance.
     * @param string $name Event name.
     * @param array  $eventArgs Event arguments.
     */
    public function fireEvents(Router $router, string $name, array $eventArgs = []): void;
}
