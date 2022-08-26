<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Routing\Route\RouteParams;

interface Routable
{
    public function handle(ServerRequestInterface $request, RouteParams $params): ResponseInterface;

    public function gatherMiddlewares(): array;

    public function getUri(): string;

    public function getMethods(): array;

    public function name(?string $name): self;

    public function domain(?string $domain): self;

    public function namespace(?string $namespace): self;

    public function middleware(): self;

    public function getName(): ?string;

    public function getDomain(): ?string;

    public function getNamespace(): ?string;
}
