<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Routing\TypeHintRequestResolver;
use ReflectionException;
use ReflectionFunction;

class TypeHintRequestResolverTest extends TestCase
{
    /** @test
     * @throws ReflectionException
     */
    public function returnsResolvedParametersWhenNoRequestIsSet()
    {
        $reflectionFunction = new ReflectionFunction(function () {
        });
        $resolvedParameters = ['a' => 123, 'b' => 456];
        $resolver           = new TypeHintRequestResolver();

        $params = $resolver->getParameters($reflectionFunction, [], $resolvedParameters);

        Assert::assertSame($resolvedParameters, $params);
    }

    /** @test
     * @throws ReflectionException
     */
    public function canResolveaRequest()
    {
        $request            = new ServerRequest([], [], '/injected', 'GET');
        $reflectionFunction = new ReflectionFunction(function (ServerRequest $request) {
        });
        $resolver           = new TypeHintRequestResolver();
        $resolver->request = $request;

        $params = $resolver->getParameters($reflectionFunction, [], []);

        Assert::assertSame('/injected', $params[0]->getUri()->getPath());
    }

    /** @test
     * @throws ReflectionException
     */
    public function doesNotAttemptToResolveParamsThatHaveAlreadyBeenResolved()
    {
        $preResolvedRequest = new ServerRequest([], [], '/pre/resolved', 'GET');
        $injectedRequest    = new ServerRequest([], [], '/injected', 'GET');
        $reflectionFunction = new ReflectionFunction(function (ServerRequest $request) {
        });
        $resolvedParameters = [0 => $preResolvedRequest];
        $resolver           = new TypeHintRequestResolver();

        $resolver->request = $injectedRequest;
        $params = $resolver->getParameters($reflectionFunction, [], $resolvedParameters);

        Assert::assertSame('/pre/resolved', $params[0]->getUri()->getPath());
    }
}
