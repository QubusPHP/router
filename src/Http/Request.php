<?php

declare(strict_types=1);

namespace Qubus\Router\Http;

use Qubus\Router\Exceptions\MalformedUrlException;
use Qubus\Router\Http\Input\Handler;

use function array_key_exists;
use function explode;
use function rtrim;
use function str_replace;
use function stripos;
use function strtolower;

class Request
{
    /**
     * Additional data
     *
     * @var array
     */
    private $data = [];

    /**
     * Server headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Request host
     *
     * @var string
     */
    protected $host;

    /**
     * Current request url
     *
     * @var Url
     */
    protected $url;

    /**
     * Request method
     *
     * @var string
     */
    protected $method;

    /**
     * Input handler
     *
     * @var Handler
     */
    protected $inputHandler;

    /**
     * Defines if request has pending rewrite
     *
     * @var bool
     */
    protected $hasPendingRewrite = false;

    /**
     * Rewrite url
     *
     * @var string|null
     */
    protected $rewriteUrl;

    /**
     * @throws MalformedUrlException
     */
    public function __construct()
    {
        foreach ($_SERVER as $key => $value) {
            $this->headers[strtolower($key)]                        = $value;
            $this->headers[strtolower(str_replace('_', '-', $key))] = $value;
        }
        $this->setHost($this->getHeader('http-host'));
        // Check if special IIS header exist, otherwise use default.
        $this->setUrl(new Url($this->getHeader('unencoded-url', $this->getHeader('request-uri'))));

        $this->method       = $this->getHeader('request-method');
        $this->inputHandler = new Handler($this);
        $this->method       = $this->inputHandler->value('_method', $this->getHeader('request-method'));
    }

    public function isSecure(): bool
    {
        return $this->getHeader('http-x-forwarded-proto') === 'https' || $this->getHeader('https') !== null || $this->getHeader('server-port') === 443;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    /**
     * Copy url object
     */
    public function getUrlCopy(): Url
    {
        return clone $this->url;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get http basic auth user
     */
    public function getUser(): ?string
    {
        return $this->getHeader('php-auth-user');
    }

    /**
     * Get http basic auth password
     */
    public function getPassword(): ?string
    {
        return $this->getHeader('php-auth-pw');
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get id address
     */
    public function getIp(): ?string
    {
        if ($this->getHeader('http-cf-connecting-ip') !== null) {
            return $this->getHeader('http-cf-connecting-ip');
        }
        if ($this->getHeader('http-x-forwarded-for') !== null) {
            return $this->getHeader('http-x-forwarded_for');
        }
        return $this->getHeader('remote-addr');
    }

    /**
     * Get remote address/ip
     *
     * @alias static::getIp
     */
    public function getRemoteAddr(): ?string
    {
        return $this->getIp();
    }

    /**
     * Get referer
     */
    public function getReferer(): ?string
    {
        return $this->getHeader('http-referer');
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): ?string
    {
        return $this->getHeader('http-user-agent');
    }

    /**
     * Get header value by name
     *
     * @param string      $name
     * @param string|null $defaultValue
     */
    public function getHeader($name, $defaultValue = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $defaultValue;
    }

    /**
     * Get input class
     */
    public function getHandler(): Handler
    {
        return $this->inputHandler;
    }

    /**
     * Is format accepted
     *
     * @param string $format
     */
    public function isFormatAccepted($format): bool
    {
        return $this->getHeader('http-accept') !== null && stripos($this->getHeader('http-accept'), $format) !== false;
    }

    /**
     * Returns true if the request is made through Ajax
     */
    public function isAjax(): bool
    {
        return strtolower($this->getHeader('http-x-requested-with')) === 'xmlhttprequest';
    }

    /**
     * Get accept formats
     */
    public function getAcceptFormats(): array
    {
        return explode(',', $this->getHeader('http-accept'));
    }

    public function setUrl(Url $url): void
    {
        $this->url = $url;
        if ($this->url->getHost() === null) {
            $this->url->setHost((string) $this->getHost());
        }
    }

    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    public function setMethod(string $method): void
    {
        $this->method = strtolower($method);
    }

    /**
     * Get rewrite url
     */
    public function getRewriteUrl(): ?string
    {
        return $this->rewriteUrl;
    }

    /**
     * Set rewrite url
     *
     * @return static
     */
    public function setRewriteUrl(string $rewriteUrl): self
    {
        $this->hasPendingRewrite = true;
        $this->rewriteUrl        = rtrim($rewriteUrl, '/') . '/';
        return $this;
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->data) === true;
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }
}
