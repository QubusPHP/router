<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Qubus\Router\Http\Request;
use Qubus\Router\Router;

interface EventArgumentInterface
{
    /**
     * Get event name
     */
    public function getEventName(): string;

    /**
     * Set event name
     */
    public function setEventName(string $name): void;

    /**
     * Get router instance
     */
    public function getRouter(): Router;

    /**
     * Get request instance
     */
    public function getRequest(): Request;

    /**
     * Get all event arguments
     */
    public function getArguments(): array;
}
