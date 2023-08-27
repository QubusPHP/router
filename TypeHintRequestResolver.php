<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Routing;

use Invoker\ParameterResolver\ParameterResolver;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;

use function array_diff_key;

final class TypeHintRequestResolver implements ParameterResolver
{
    protected ServerRequestInterface $request;

    /**
     * @throws ReflectionException
     */
    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ): array {
        if (! isset($this->request)) {
            return $resolvedParameters;
        }
        $parameters = $reflection->getParameters();

        /**
         * Skip parameters already resolved
         */
        if (! empty($resolvedParameters)) {
            $parameters = array_diff_key($parameters, $resolvedParameters);
        }

        foreach ($parameters as $index => $parameter) {
            $parameterClass = $parameter->getType() && ! $parameter->getType()->isBuiltin()
            ? new ReflectionClass(objectOrClass: $parameter->getType()->getName())
            : null;

            if (! $parameterClass) {
                continue;
            }

            if ($parameterClass->implementsInterface(interface: ServerRequestInterface::class)) {
                $resolvedParameters[$index] = $this->createRequestOfType(requestClass: $parameterClass);
            }
        }
        return $resolvedParameters;
    }

    /**
     * @throws ReflectionException
     */
    protected function createRequestOfType(ReflectionClass $requestClass): mixed
    {
        return $requestClass->newInstance(
            $this->request->getServerParams(),
            $this->request->getUploadedFiles(),
            $this->request->getUri(),
            $this->request->getMethod(),
            $this->request->getBody(),
            $this->request->getHeaders(),
            $this->request->getCookieParams(),
            $this->request->getQueryParams(),
            $this->request->getParsedBody(),
            $this->request->getProtocolVersion()
        );
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}
