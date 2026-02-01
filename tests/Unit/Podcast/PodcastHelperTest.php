<?php

/**
 * Tests for PodcastHelper.
 *
 * @package SermonBrowser\Tests\Unit\Podcast
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Podcast;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Podcast\PodcastHelper;
use SermonBrowser\Repositories\FileRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test PodcastHelper functionality.
 */
class PodcastHelperTest extends TestCase
{
    /**
     * Mock file repository.
     *
     * @var \Mockery\MockInterface&FileRepository
     */
    private $mockFileRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset container and set up mock repository
        Container::reset();
        $this->mockFileRepo = Mockery::mock(FileRepository::class);
        Container::getInstance()->set(FileRepository::class, $this->mockFileRepo);

        // Reset the static filetypes cache
        $reflection = new \ReflectionClass(PodcastHelper::class);
        $property = $reflection->getProperty('filetypes');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    // =========================================================================
    // formatIsoDate tests
    // =========================================================================

    /**
     * Test formatIsoDate with sermon object.
     */
    public function testFormatIsoDateWithSermonObject(): void
    {
        $sermon = (object) ['datetime' => '2024-01-15 10:30:00'];

        $result = PodcastHelper::formatIsoDate($sermon);

        // RFC 2822 format: Mon, 15 Jan 2024 10:30:00 +0000
        $this->assertMatchesRegularExpression(
            '/^Mon, 15 Jan 2024 10:30:00 [+-]\d{4}$/',
            $result
        );
    }

    /**
     * Test formatIsoDate with timestamp integer.
     */
    public function testFormatIsoDateWithTimestamp(): void
    {
        // 2024-01-15 10:30:00 UTC
        $timestamp = 1705315800;

        $result = PodcastHelper::formatIsoDate($timestamp);

        $this->assertMatchesRegularExpression(
            '/^Mon, 15 Jan 2024 \d{2}:\d{2}:\d{2} [+-]\d{4}$/',
            $result
        );
    }

    /**
     * Test formatIsoDate with date string.
     */
    public function testFormatIsoDateWithDateString(): void
    {
        $result = PodcastHelper::formatIsoDate('2024-06-20 14:00:00');

        // RFC 2822 format: Thu, 20 Jun 2024 14:00:00 +0000
        $this->assertMatchesRegularExpression(
            '/^Thu, 20 Jun 2024 14:00:00 [+-]\d{4}$/',
            $result
        );
    }

    // =========================================================================
    // xmlEncode tests
    // =========================================================================

    /**
     * Test xmlEncode encodes ampersand.
     */
    public function testXmlEncodeEncodesAmpersand(): void
    {
        $result = PodcastHelper::xmlEncode('Tom & Jerry');

        $this->assertSame('Tom &amp; Jerry', $result);
    }

    /**
     * Test xmlEncode handles already encoded ampersand.
     */
    public function testXmlEncodeHandlesAlreadyEncodedAmpersand(): void
    {
        $result = PodcastHelper::xmlEncode('Tom &amp; Jerry');

        // Should not double-encode
        $this->assertSame('Tom &amp; Jerry', $result);
    }

    /**
     * Test xmlEncode handles double-encoded ampersand from input.
     *
     * The logic: first all & become &amp;, then &amp;amp; is fixed to &amp;.
     * Input 'Tom &amp;amp; Jerry' becomes 'Tom &amp;amp;amp; Jerry' then fixed to 'Tom &amp;amp; Jerry'.
     */
    public function testXmlEncodeHandlesDoubleEncodedAmpersand(): void
    {
        $result = PodcastHelper::xmlEncode('Tom &amp;amp; Jerry');

        // After encoding & to &amp;, &amp;amp; to &amp;: results in &amp;amp;
        $this->assertSame('Tom &amp;amp; Jerry', $result);
    }

    /**
     * Test xmlEncode encodes double quotes.
     */
    public function testXmlEncodeEncodesDoubleQuotes(): void
    {
        $result = PodcastHelper::xmlEncode('He said "Hello"');

        $this->assertSame('He said &quot;Hello&quot;', $result);
    }

    /**
     * Test xmlEncode encodes single quotes.
     */
    public function testXmlEncodeEncodesSingleQuotes(): void
    {
        $result = PodcastHelper::xmlEncode("It's a test");

        $this->assertSame("It&apos;s a test", $result);
    }

    /**
     * Test xmlEncode encodes less than symbol.
     */
    public function testXmlEncodeEncodesLessThan(): void
    {
        $result = PodcastHelper::xmlEncode('5 < 10');

        $this->assertSame('5 &lt; 10', $result);
    }

    /**
     * Test xmlEncode encodes greater than symbol.
     */
    public function testXmlEncodeEncodesGreaterThan(): void
    {
        $result = PodcastHelper::xmlEncode('10 > 5');

        $this->assertSame('10 &gt; 5', $result);
    }

    /**
     * Test xmlEncode handles multiple special characters.
     */
    public function testXmlEncodeHandlesMultipleSpecialCharacters(): void
    {
        $result = PodcastHelper::xmlEncode('<tag attr="value">Tom & Jerry\'s</tag>');

        $this->assertSame(
            '&lt;tag attr=&quot;value&quot;&gt;Tom &amp; Jerry&apos;s&lt;/tag&gt;',
            $result
        );
    }

    /**
     * Test xmlEncode with empty string.
     */
    public function testXmlEncodeWithEmptyString(): void
    {
        $result = PodcastHelper::xmlEncode('');

        $this->assertSame('', $result);
    }

    /**
     * Test xmlEncode with plain text.
     */
    public function testXmlEncodeWithPlainText(): void
    {
        $result = PodcastHelper::xmlEncode('Hello World');

        $this->assertSame('Hello World', $result);
    }

    // =========================================================================
    // getMediaSize tests
    // =========================================================================

    /**
     * Test getMediaSize returns length attribute for Files.
     */
    public function testGetMediaSizeReturnsLengthAttributeForFiles(): void
    {
        Functions\when('sb_get_option')->justReturn('uploads/sermons/');

        // File doesn't exist, so size will be 0
        $result = PodcastHelper::getMediaSize('sermon.mp3', 'Files');

        $this->assertSame('length="0"', $result);
    }

    /**
     * Test getMediaSize format for non-existent file.
     */
    public function testGetMediaSizeFormatForNonExistentFile(): void
    {
        Functions\when('sb_get_option')->justReturn('uploads/sermons/');

        $result = PodcastHelper::getMediaSize('nonexistent.mp3', 'Files');

        // Should return length="0" for non-existent files
        $this->assertMatchesRegularExpression('/^length="\d+"$/', $result);
    }

    // Note: getMediaSize for URLs (lines 63-70) requires mocking native PHP functions
    // (ini_get, get_headers) which needs patchwork.json configuration. Skipped for now.

    // =========================================================================
    // getMp3Duration tests
    // =========================================================================

    /**
     * Test getMp3Duration returns empty for non-mp3 files.
     */
    public function testGetMp3DurationReturnsEmptyForNonMp3(): void
    {
        $result = PodcastHelper::getMp3Duration('video.mp4', 'Files');

        $this->assertSame('', $result);
    }

    /**
     * Test getMp3Duration returns empty for URLs.
     */
    public function testGetMp3DurationReturnsEmptyForUrls(): void
    {
        $result = PodcastHelper::getMp3Duration('sermon.mp3', 'URLs');

        $this->assertSame('', $result);
    }

    /**
     * Test getMp3Duration returns empty for non-mp3 extension.
     */
    public function testGetMp3DurationChecksExtension(): void
    {
        $result = PodcastHelper::getMp3Duration('file.wav', 'Files');

        $this->assertSame('', $result);
    }

    /**
     * Test getMp3Duration returns cached duration when available.
     */
    public function testGetMp3DurationReturnsCachedDuration(): void
    {
        $this->mockFileRepo->shouldReceive('getFileDuration')
            ->once()
            ->with('sermon.mp3')
            ->andReturn('01:23:45');

        $result = PodcastHelper::getMp3Duration('sermon.mp3', 'Files');

        $this->assertSame('01:23:45', $result);
    }

    /**
     * Test getMp3Duration with uppercase MP3 extension.
     */
    public function testGetMp3DurationWithUppercaseExtension(): void
    {
        $this->mockFileRepo->shouldReceive('getFileDuration')
            ->once()
            ->with('sermon.MP3')
            ->andReturn('00:30:00');

        $result = PodcastHelper::getMp3Duration('sermon.MP3', 'Files');

        $this->assertSame('00:30:00', $result);
    }

    // =========================================================================
    // getFileUrl tests
    // =========================================================================

    /**
     * Test getFileUrl for URLs with stats enabled.
     */
    public function testGetFileUrlForUrlsWithStats(): void
    {
        // Clear user agent to enable stats
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';

        Functions\when('sb_display_url')->justReturn('http://example.com/sermons');
        Functions\when('sb_query_char')->justReturn('?');

        $result = PodcastHelper::getFileUrl('https://cdn.example.com/sermon.mp3', 'URLs');

        // The &amp; in the code gets processed through xmlEncode, resulting in &amp;
        $this->assertStringContainsString('show&amp;url=', $result);
        $this->assertStringContainsString(rawurlencode('https://cdn.example.com/sermon.mp3'), $result);
    }

    /**
     * Test getFileUrl for URLs with iTunes user agent disables stats.
     */
    public function testGetFileUrlForUrlsWithItunesDisablesStats(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'iTunes/12.0';

        $result = PodcastHelper::getFileUrl('https://cdn.example.com/sermon.mp3', 'URLs');

        // Should return the original URL (XML encoded)
        $this->assertSame('https://cdn.example.com/sermon.mp3', $result);
    }

    /**
     * Test getFileUrl for URLs with FeedBurner user agent disables stats.
     */
    public function testGetFileUrlForUrlsWithFeedburnerDisablesStats(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'FeedBurner/1.0';

        $result = PodcastHelper::getFileUrl('https://cdn.example.com/sermon.mp3', 'URLs');

        $this->assertSame('https://cdn.example.com/sermon.mp3', $result);
    }

    /**
     * Test getFileUrl for URLs with AppleCoreMedia user agent disables stats.
     */
    public function testGetFileUrlForUrlsWithApplecoremediaDisablesStats(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'AppleCoreMedia/1.0';

        $result = PodcastHelper::getFileUrl('https://cdn.example.com/sermon.mp3', 'URLs');

        $this->assertSame('https://cdn.example.com/sermon.mp3', $result);
    }

    /**
     * Test getFileUrl for Files with stats enabled.
     */
    public function testGetFileUrlForFilesWithStats(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';

        Functions\when('sb_display_url')->justReturn('http://example.com/sermons');
        Functions\when('sb_query_char')->justReturn('?');

        $result = PodcastHelper::getFileUrl('sermon.mp3', 'Files');

        // The &amp; in the code gets processed through xmlEncode, resulting in &amp;
        $this->assertStringContainsString('show&amp;file_name=', $result);
        $this->assertStringContainsString(rawurlencode('sermon.mp3'), $result);
    }

    /**
     * Test getFileUrl for Files with iTunes user agent returns direct URL.
     */
    public function testGetFileUrlForFilesWithItunesReturnsDirectUrl(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'iTunes/12.0';

        Functions\when('site_url')->justReturn('http://example.com');
        Functions\when('trailingslashit')->alias(function ($url) {
            return rtrim($url, '/') . '/';
        });
        Functions\when('sb_get_option')->justReturn('wp-content/uploads/sermons/');

        $result = PodcastHelper::getFileUrl('sermon.mp3', 'Files');

        $this->assertStringContainsString('http://example.com/', $result);
        $this->assertStringContainsString('wp-content/uploads/sermons/', $result);
        $this->assertStringContainsString(rawurlencode('sermon.mp3'), $result);
    }

    // =========================================================================
    // getMimeType tests
    // =========================================================================

    /**
     * Test getMimeType returns type attribute for known extension.
     */
    public function testGetMimeTypeReturnsTypeForKnownExtension(): void
    {
        // Set filetypes via reflection
        $reflection = new \ReflectionClass(PodcastHelper::class);
        $property = $reflection->getProperty('filetypes');
        $property->setAccessible(true);
        $property->setValue(null, [
            'mp3' => ['content-type' => 'audio/mpeg'],
            'mp4' => ['content-type' => 'video/mp4'],
        ]);

        $result = PodcastHelper::getMimeType('sermon.mp3');

        $this->assertSame(' type="audio/mpeg"', $result);
    }

    /**
     * Test getMimeType returns empty for unknown extension.
     */
    public function testGetMimeTypeReturnsEmptyForUnknownExtension(): void
    {
        // Set filetypes via reflection
        $reflection = new \ReflectionClass(PodcastHelper::class);
        $property = $reflection->getProperty('filetypes');
        $property->setAccessible(true);
        $property->setValue(null, [
            'mp3' => ['content-type' => 'audio/mpeg'],
        ]);

        $result = PodcastHelper::getMimeType('document.pdf');

        $this->assertSame('', $result);
    }

    /**
     * Test getMimeType handles uppercase extension.
     */
    public function testGetMimeTypeHandlesUppercaseExtension(): void
    {
        // Set filetypes via reflection
        $reflection = new \ReflectionClass(PodcastHelper::class);
        $property = $reflection->getProperty('filetypes');
        $property->setAccessible(true);
        $property->setValue(null, [
            'mp3' => ['content-type' => 'audio/mpeg'],
        ]);

        $result = PodcastHelper::getMimeType('sermon.MP3');

        $this->assertSame(' type="audio/mpeg"', $result);
    }

    /**
     * Test getMimeType with video extension.
     */
    public function testGetMimeTypeForVideo(): void
    {
        // Set filetypes via reflection
        $reflection = new \ReflectionClass(PodcastHelper::class);
        $property = $reflection->getProperty('filetypes');
        $property->setAccessible(true);
        $property->setValue(null, [
            'mp4' => ['content-type' => 'video/mp4'],
            'm4v' => ['content-type' => 'video/x-m4v'],
        ]);

        $result = PodcastHelper::getMimeType('video.mp4');

        $this->assertSame(' type="video/mp4"', $result);
    }

    /**
     * Test getMimeType with file having multiple dots.
     */
    public function testGetMimeTypeWithMultipleDots(): void
    {
        // Set filetypes via reflection
        $reflection = new \ReflectionClass(PodcastHelper::class);
        $property = $reflection->getProperty('filetypes');
        $property->setAccessible(true);
        $property->setValue(null, [
            'mp3' => ['content-type' => 'audio/mpeg'],
        ]);

        $result = PodcastHelper::getMimeType('sermon.2024.01.15.mp3');

        $this->assertSame(' type="audio/mpeg"', $result);
    }

    // =========================================================================
    // getFileUrl edge cases
    // =========================================================================

    /**
     * Test getFileUrl with no user agent defaults to stats enabled.
     */
    public function testGetFileUrlWithNoUserAgentDefaultsToStats(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        Functions\when('sb_display_url')->justReturn('http://example.com/sermons');
        Functions\when('sb_query_char')->justReturn('?');

        $result = PodcastHelper::getFileUrl('https://cdn.example.com/sermon.mp3', 'URLs');

        $this->assertStringContainsString('show&amp;url=', $result);
    }

    /**
     * Test getFileUrl encodes special characters in URL.
     */
    public function testGetFileUrlEncodesSpecialCharacters(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'iTunes/12.0';

        $result = PodcastHelper::getFileUrl('https://cdn.example.com/Tom & Jerry.mp3', 'URLs');

        // Ampersand should be XML encoded
        $this->assertStringContainsString('&amp;', $result);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        parent::tearDown();
    }
}
