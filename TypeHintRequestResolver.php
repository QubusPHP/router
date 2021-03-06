<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing;

use Invoker\ParameterResolver\ParameterResolver;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionFunctionAbstract;

use function array_diff_key;

final class TypeHintRequestResolver implements ParameterResolver
{
    protected $request;

    public function getParameters(
        ReflectionFunctionAbstract $reflection,
        array $providedParameters,
        array $resolvedParameters
    ) {
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
            $parameterClass = $parameter->getClass();

            if (! $parameterClass) {
                continue;
            }

            if ($parameterClass->implementsInterface(ServerRequestInterface::class)) {
                $resolvedParameters[$index] = $this->createRequestOfType($parameterClass);
            }
        }
        return $resolvedParameters;
    }

    protected function createRequestOfType(ReflectionClass $requestClass)
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

    public function setRequest(ServerRequest $request)
    {
        $this->request = $request;
    }
}
