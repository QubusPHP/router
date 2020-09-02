<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Interface PublisherInterface
 * Publishers are responsible to publish the response provided by controllers.
 */
interface HttpPublisherInterface
{
    /**
     * Publish the content.
     *
     * @param PsrResponseInterface|StreamInterface $content
     */
    public function publish($content, ?EmitterInterface $response): bool;
}
