<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Qubus\Routing\Controller\ControllerMiddlewareOptions;

class ControllerMiddlewareOptionsTest extends TestCase
{
    /** @test */
    public function byDefaultNoMethodsAreExcluded()
    {
        $options = new ControllerMiddlewareOptions();

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
    }

    /** @test */
    public function onlyIsChainable()
    {
        $options = new ControllerMiddlewareOptions();

        $this->assertSame($options, $options->only('foo'));
    }

    /** @test */
    public function canUseOnlyToLimitMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->only('foo');

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertTrue($options->excludedForMethod('bar'));
    }

    /** @test */
    public function canUseOnlyToLimitMultipleMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->only(['foo', 'bar']);

        $this->assertFalse($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
        $this->assertTrue($options->excludedForMethod('baz'));
    }

    /** @test */
    public function exceptIsChainable()
    {
        $options = new ControllerMiddlewareOptions();

        $this->assertSame($options, $options->except('foo'));
    }

    /** @test */
    public function canUseExceptToLimitMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->except('foo');

        $this->assertTrue($options->excludedForMethod('foo'));
        $this->assertFalse($options->excludedForMethod('bar'));
    }

    /** @test */
    public function canUseExceptToLimitMultipleMethods()
    {
        $options = new ControllerMiddlewareOptions();

        $options->except(['foo', 'bar']);

        $this->assertTrue($options->excludedForMethod('foo'));
        $this->assertTrue($options->excludedForMethod('bar'));
        $this->assertFalse($options->excludedForMethod('baz'));
    }
}
