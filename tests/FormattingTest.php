<?php

namespace Qubus\Router\Test;

use PHPUnit\Framework\TestCase;
use Qubus\Router\Formatting;

class FormattingTest extends TestCase
{
    /** @test */
    public function canRemoveTrailingSlash()
    {
        $string = 'string/';

        $this->assertSame('string', Formatting::removeTrailingSlash($string));
    }

    /** @test */
    public function canAddTrailingSlash()
    {
        $string = 'string';

        $this->assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function addTrailingSlashDoesNotProduceDuplicates()
    {
        $string = 'string/';

        $this->assertSame('string/', Formatting::addTrailingSlash($string));
    }

    /** @test */
    public function canRemoveLeadingSlash()
    {
        $string = '/string';

        $this->assertSame('string', Formatting::removeLeadingSlash($string));
    }

    /** @test */
    public function canAddLeadingSlash()
    {
        $string = 'string';

        $this->assertSame('/string', Formatting::addLeadingSlash($string));
    }

    /** @test */
    public function addLeadingSlashDoesNotProduceDuplicates()
    {
        $string = '/string';

        $this->assertSame('/string', Formatting::addLeadingSlash($string));
    }
}
