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

namespace Qubus\Routing\Events;

use Closure;
use Qubus\Routing\Router;

class RoutingEventHandler implements EventHandler
{
    /**
     * Fires when a event is triggered.
     */
    public const EVENT_ALL = '*';

    /**
     * Fires when router is initializing and before routes are loaded.
     */
    public const EVENT_INIT = 'onInit';

    /**
     * Fires when all routes have been loaded and rendered, just before the
     * output is returned.
     */
    public const EVENT_LOAD = 'onLoad';

    /**
     * Fires when route is added to the router
     */
    public const EVENT_ADD_ROUTE = 'onAddRoute';

    /**
     * Fires when the router is booting. This happens just before boot-managers
     * are rendered and before any routes has been loaded.
     */
    public const EVENT_BOOT = 'onBoot';

    /**
     * Fires before a boot-manager is rendered.
     */
    public const EVENT_RENDER_BOOTMANAGER = 'onRenderBootManager';

    /**
     * Fires when the router is about to load all routes.
     */
    public const EVENT_LOAD_ROUTES = 'onLoadRoutes';

    /**
     * Fires whenever the `has` method is used.
     */
    public const EVENT_FIND_ROUTE = 'onFindRoute';

    /**
     * Fires whenever the `Router::url` method is called and the router tries
     * to find the route.
     */
    public const EVENT_GET_URL = 'onGetUrl';

    /**
     * Fires when a route is matched and valid (correct request-type etc).
     * and before the route is rendered.
     */
    public const EVENT_MATCH_ROUTE = 'onMatchRoute';

    /**
     * Fires before a middleware is rendered.
     */
    public const EVENT_RENDER_MIDDLEWARES = 'onRenderMiddlewares';

    /**
     * All available events
     *
     * @var array $events
     */
    public static array $events = [
        self::EVENT_ALL,
        self::EVENT_INIT,
        self::EVENT_LOAD,
        self::EVENT_ADD_ROUTE,
        self::EVENT_BOOT,
        self::EVENT_RENDER_BOOTMANAGER,
        self::EVENT_LOAD_ROUTES,
        self::EVENT_FIND_ROUTE,
        self::EVENT_GET_URL,
        self::EVENT_MATCH_ROUTE,
        self::EVENT_RENDER_MIDDLEWARES,
    ];

    /**
     * List of all registered events.
     *
     * @var array $registeredEvents
     */
    private array $registeredEvents = [];

    /**
     * Register new event.
     *
     * @return static
     */
    public function register(string $name, Closure $callback): EventHandler
    {
        if (isset($this->registeredEvents[$name]) === true) {
            $this->registeredEvents[$name][] = $callback;
        } else {
            $this->registeredEvents[$name] = [$callback];
        }
        return $this;
    }

    /**
     * Get events.
     *
     * @param string|null $name Filter events by name.
     * @param array       ...$names Add multiple names...
     */
    public function getEvents(?string $name, ...$names): array
    {
        if ($name === null) {
            return $this->registeredEvents;
        }

        $names[] = $name;
        $events  = [];

        foreach ($names as $eventName) {
            if (isset($this->registeredEvents[$eventName]) === true) {
                $events += $this->registeredEvents[$eventName];
            }
        }
        return $events;
    }

    /**
     * Fires any events registered with given event-name
     *
     * @param Router $router Router instance
     * @param string $name Event name
     * @param array  $eventArgs Event arguments
     */
    public function fireEvents(Router $router, string $name, array $eventArgs = []): void
    {
        $events = $this->getEvents(static::EVENT_ALL, $name);

        /** @var Closure $event */
        foreach ($events as $event) {
            $event(new RoutingEventArgument(eventName: $name, router: $router, arguments: $eventArgs));
        }
    }
}
