<?php

declare(strict_types=1);

namespace Qubus\Router\Factories;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Router\Interfaces\ResponsableInterface;
use Qubus\Router\Interfaces\ResponseFactoryInterface;

final class ResponseFactory implements ResponseFactoryInterface
{
    public static function create(RequestInterface $request, $response = '')
    {
        if (empty($response)) {
            return new EmptyResponse();
        }

        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if ($response instanceof ResponsableInterface) {
            return $response->toResponse($request);
        }

        return new HtmlResponse($response);
    }
}
