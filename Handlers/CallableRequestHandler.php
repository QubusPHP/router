<?php

declare(strict_types=1);

namespace Qubus\Routing\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CallableRequestHandler implements RequestHandlerInterface
{
    /**
     * @var callable(ServerRequestInterface): ResponseInterface
     *
     * @readonly
     */
    private $callback;

    /**
     * @param callable(ServerRequestInterface): ResponseInterface $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->callback)($request);
    }
}
