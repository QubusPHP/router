<?php

declare(strict_types=1);

namespace Qubus\Routing\Tests\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AddHeaderAliasMiddleware implements MiddlewareInterface
{
    private ?string $key = null;
    private ?string $value = null;

    public function withArguments(string $key, string $value): static
    {
        $clone = clone $this;
        $clone->key = $key;
        $clone->value = $value;
        return $clone;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader(
            $this->key,
            $this->value
        )->withStatus(200);
    }
}
