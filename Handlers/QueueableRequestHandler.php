<?php

declare(strict_types=1);

namespace Qubus\Routing\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

final class QueueableRequestHandler extends SplQueue implements RequestHandlerInterface
{
    public function __construct(
        private readonly RequestHandlerInterface $endpoint,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->isEmpty()
        ? $this->endpoint->handle($request)
        : ($clone = clone $this)->dequeue()->process($request, $clone);
    }
}
