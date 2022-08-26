<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Routing\Formatting;

class FormattingTest extends TestCase
{
    /** @test */
    public function canRemoveTrailingSlash()
    {
        $string = 'string/';

        Assert::assertSame('string', Formatting::removeTrailingSlash($string));
    }

    /** @test */
    public function canAddTrailingSlash()
    {
        $string = 'string';

        Assert::assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function addTrailingSlashDoesNotProduceDuplicates()
    {
        $string = 'string/';

        Assert::assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function canRemoveLeadingSlash()
    {
        $string = '/string';

        Assert::assertSame('string', Formatting::removeLeadingSlash($string));
    }

    /** @test */
    public function canAddLeadingSlash()
    {
        $string = 'string';

        Assert::assertSame('/string', Formatting::addLeadingSlash($string));
    }

    /** @test */
    public function addLeadingSlashDoesNotProduceDuplicates()
    {
        $string = '/string';

        Assert::assertSame('/string', Formatting::addLeadingSlash($string));
    }
}
