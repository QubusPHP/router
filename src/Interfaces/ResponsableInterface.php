<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ResponsableInterface
{
    public function toResponse(RequestInterface $request): ResponseInterface;
}
