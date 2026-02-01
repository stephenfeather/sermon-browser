<?php

/**
 * Tests for Frontend\StyleOutput class.
 *
 * @package SermonBrowser\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Frontend;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Frontend\StyleOutput;
use Brain\Monkey\Functions;

/**
 * Test class for StyleOutput.
 */
class StyleOutputTest extends TestCase
{
    /**
     * Test getStyleContent retrieves CSS from options.
     */
    public function testGetStyleContentRetrievesCssFromOptions(): void
    {
        $expectedCss = 'body { color: red; }';

        Functions\expect('sb_get_option')
            ->with('css_style')
            ->once()
            ->andReturn($expectedCss);

        $result = StyleOutput::getStyleContent();

        $this->assertEquals($expectedCss, $result);
    }

    /**
     * Test getLastModified retrieves timestamp from options.
     */
    public function testGetLastModifiedRetrievesTimestamp(): void
    {
        $timestamp = 1700000000;

        Functions\expect('sb_get_option')
            ->with('style_date_modified')
            ->once()
            ->andReturn($timestamp);

        $result = StyleOutput::getLastModified();

        $this->assertEquals($timestamp, $result);
    }

    /**
     * Test shouldReturn304 returns true when not modified.
     */
    public function testShouldReturn304WhenNotModified(): void
    {
        $lastModified = 1700000000;
        $ifModifiedSince = 'Tue, 14 Nov 2023 22:13:20 GMT';

        $result = StyleOutput::shouldReturn304($lastModified, $ifModifiedSince);

        $this->assertTrue($result);
    }

    /**
     * Test shouldReturn304 returns false when modified.
     */
    public function testShouldReturn304WhenModified(): void
    {
        $lastModified = 1700000000; // Newer
        $ifModifiedSince = 'Mon, 01 Jan 2023 00:00:00 GMT'; // Older

        $result = StyleOutput::shouldReturn304($lastModified, $ifModifiedSince);

        $this->assertFalse($result);
    }

    /**
     * Test shouldReturn304 returns false when header is empty.
     */
    public function testShouldReturn304ReturnsFalseWhenHeaderEmpty(): void
    {
        $lastModified = 1700000000;

        $result = StyleOutput::shouldReturn304($lastModified, '');

        $this->assertFalse($result);
    }

    /**
     * Test getCacheMaxAge returns expected value.
     */
    public function testGetCacheMaxAge(): void
    {
        $result = StyleOutput::getCacheMaxAge();

        // 7 days in seconds
        $expected = 60 * 60 * 24 * 7;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test formatGmtDate formats timestamp correctly.
     */
    public function testFormatGmtDate(): void
    {
        $timestamp = 1700000000;

        $result = StyleOutput::formatGmtDate($timestamp);

        $this->assertStringContainsString('GMT', $result);
        $this->assertMatchesRegularExpression('/[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4}/', $result);
    }
}
