<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Routing\Controller\ControllerMiddlewareOptions;

class ControllerMiddlewareOptionsTest extends TestCase
{
    /** @test */
    public function testByDefaultNoMethodsAreExcluded()
    {
        $options = new ControllerMiddlewareOptions();

        Assert::assertFalse($options->excludedForMethod('foo'));
        Assert::assertFalse($options->excludedForMethod('bar'));
    }

    /** @test */
    public function testOnlyIsChainable()
    {
        $options = new ControllerMiddlewareOptions();

        Assert::assertSame($options, $options->only('foo'));
    }

    /** @test */
    public function testCanUseOnlyToLimitMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->only('foo');

        Assert::assertFalse($options->excludedForMethod('foo'));
        Assert::assertTrue($options->excludedForMethod('bar'));
    }

    /** @test */
    public function testCanUseOnlyToLimitMultipleMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->only(['foo', 'bar']);

        Assert::assertFalse($options->excludedForMethod('foo'));
        Assert::assertFalse($options->excludedForMethod('bar'));
        Assert::assertTrue($options->excludedForMethod('baz'));
    }

    /** @test */
    public function testExceptIsChainable()
    {
        $options = new ControllerMiddlewareOptions();

        Assert::assertSame($options, $options->except('foo'));
    }

    /** @test */
    public function testCanUseExceptToLimitMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->except('foo');

        Assert::assertTrue($options->excludedForMethod('foo'));
        Assert::assertFalse($options->excludedForMethod('bar'));
    }

    /** @test */
    public function testCanUseExceptToLimitMultipleMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->except(['foo', 'bar']);

        Assert::assertTrue($options->excludedForMethod('foo'));
        Assert::assertTrue($options->excludedForMethod('bar'));
        Assert::assertFalse($options->excludedForMethod('baz'));
    }
}
