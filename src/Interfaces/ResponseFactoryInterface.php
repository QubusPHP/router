<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Psr\Http\Message\RequestInterface;

interface ResponseFactoryInterface
{
    public static function create(RequestInterface $request, $response = '');
}
