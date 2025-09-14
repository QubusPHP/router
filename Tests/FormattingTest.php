<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Routing\Formatting;

class FormattingTest extends TestCase
{
    /** @test */
    public function testCanRemoveTrailingSlash()
    {
        $string = 'string/';

        Assert::assertSame('string', Formatting::removeTrailingSlash($string));
    }

    /** @test */
    public function testCanAddTrailingSlash()
    {
        $string = 'string';

        Assert::assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function testAddTrailingSlashDoesNotProduceDuplicates()
    {
        $string = 'string/';

        Assert::assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function testCanRemoveLeadingSlash()
    {
        $string = '/string';

        Assert::assertSame('string', Formatting::removeLeadingSlash($string));
    }

    /** @test */
    public function testCanAddLeadingSlash()
    {
        $string = 'string';

        Assert::assertSame('/string', Formatting::addLeadingSlash($string));
    }

    /** @test */
    public function testAddLeadingSlashDoesNotProduceDuplicates()
    {
        $string = '/string';

        Assert::assertSame('/string', Formatting::addLeadingSlash($string));
    }
}
