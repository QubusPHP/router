<?php

declare(strict_types=1);

namespace Qubus\Routing\Tests\Fixtures;

use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Routing\Interfaces\Responsable;

class ResponsableObject implements Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface
    {
        return new TextResponse('testing123');
    }
}
