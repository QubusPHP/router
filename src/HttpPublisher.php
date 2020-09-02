<?php

declare(strict_types=1);

namespace Qubus\Router;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Qubus\Router\Interfaces\HttpPublisherInterface;

use function flush;
use function function_exists;
use function header;
use function sprintf;
use function ucwords;

/**
 * StreamPublisher publishes the given response.
 */
class HttpPublisher implements HttpPublisherInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function publish($content, ?EmitterInterface $emitter): bool
    {
        $content = empty($content) ? '' : $content;

        if (null !== $emitter && $content instanceof ResponseInterface) {
            try {
                return $emitter->emit($content);
            } finally {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
            }
        }

        if (null === $emitter && $content instanceof ResponseInterface) {
            $this->emitResponseHeaders($content);
            $content = $content->getBody();
        }

        flush();

        if ($content instanceof StreamInterface) {
            try {
                return $this->emitStreamBody($content);
            } finally {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
            }
        }
        return new HtmlResponse(
            'The response body must be an instance of ResponseInterface or StreamInterface',
            200,
            ['Content-Type' => ['application/xhtml+xml']]
        );
    }

    /**
     * Emit the message body.
     */
    private function emitStreamBody(StreamInterface $body): bool
    {
        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (! $body->isReadable()) {
            echo $body;

            return true;
        }

        while (! $body->eof()) {
            echo $body->read(8192);
        }

        return true;
    }

    /**
     * Emit the response header.
     *
     * @param ResponseInterface $response
     */
    private function emitResponseHeaders(PsrResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $name => $values) {
            $name  = ucwords($name, '-'); // Filter a header name to wordcase
            $first = $name !== 'Set-Cookie';

            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $first, $statusCode);
                $first = false;
            }
        }
    }
}
