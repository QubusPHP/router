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

namespace Qubus\Routing;

use Invoker\Invoker as DIInvoker;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

final class Invoker extends DIInvoker
{
    protected TypeHintRequestResolver $requestResolver;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct(parameterResolver: null, container: $container);

        $resolver = $this->getParameterResolver();

        /**
         * Allow the invoker to resolve dependencies via Type Hinting.
         *
         * @var ContainerInterface $container
         */
        $containerResolver = new TypeHintContainerResolver($container);
        $resolver->prependResolver(resolver: $containerResolver);

        $this->requestResolver = new TypeHintRequestResolver();
        $resolver->prependResolver(resolver: $this->requestResolver);
    }

    public function setRequest(ServerRequestInterface $request): self
    {
        $this->requestResolver->setRequest(request: $request);

        return $this;
    }
}
