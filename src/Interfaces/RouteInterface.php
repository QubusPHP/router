<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Qubus\Router\Route\RouteParams;

interface RouteInterface
{
    public function handle(ServerRequest $request, RouteParams $params): ResponseInterface;

    public function gatherMiddlewares(): array;

    public function getUri(): string;

    public function getMethods(): array;

    public function name(?string $name): self;

    public function domain(?string $domain): self;

    public function subDomain(?string $subdomain): self;

    public function namespace(?string $namespace): self;

    public function middleware(): self;

    public function getName(): ?string;

    public function getDomain(): ?string;

    public function getSubDomain(): ?string;

    public function getNamespace(): ?string;
}
