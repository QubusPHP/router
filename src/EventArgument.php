<?php

declare(strict_types=1);

namespace Qubus\Router;

use Qubus\Exception\Data\TypeException;
use Qubus\Router\Http\Request;
use Qubus\Router\Interfaces\EventArgumentInterface;

use function array_key_exists;

class EventArgument implements EventArgumentInterface
{
    /**
     * Event name
     *
     * @var string
     */
    protected $eventName;

    /** @var Router */
    protected $router;

    /** @var array */
    protected $arguments = [];

    public function __construct($eventName, $router, array $arguments = [])
    {
        $this->eventName = $eventName;
        $this->router    = $router;
        $this->arguments = $arguments;
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Set the event name
     */
    public function setEventName(string $name): void
    {
        $this->eventName = $name;
    }

    /**
     * Get the router instance
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the request instance
     */
    public function getRequest(): Request
    {
        return $this->getRouter()->getRequest();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return \array_key_exists($name, $this->arguments);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @throws TypeException
     */
    public function __set($name, $value)
    {
        throw new TypeException('Not supported');
    }

    /**
     * Get arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
