<?php

/**
 * Tests for PodcastFeed.
 *
 * @package SermonBrowser\Tests\Unit\Podcast
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Podcast;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Podcast\PodcastFeed;
use ReflectionClass;

/**
 * Test PodcastFeed functionality.
 *
 * Note: PodcastFeed is primarily an output class that renders RSS XML.
 * These tests verify the class configuration and constants.
 * Full integration testing would require a WordPress environment.
 */
class PodcastFeedTest extends TestCase
{
    /**
     * Test ACCEPTED_EXTENSIONS constant contains expected audio formats.
     */
    public function testAcceptedExtensionsContainsAudioFormats(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $constant = $reflection->getConstant('ACCEPTED_EXTENSIONS');

        $this->assertContains('mp3', $constant);
        $this->assertContains('m4a', $constant);
    }

    /**
     * Test ACCEPTED_EXTENSIONS constant contains expected video formats.
     */
    public function testAcceptedExtensionsContainsVideoFormats(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $constant = $reflection->getConstant('ACCEPTED_EXTENSIONS');

        $this->assertContains('mp4', $constant);
        $this->assertContains('m4v', $constant);
        $this->assertContains('mov', $constant);
    }

    /**
     * Test ACCEPTED_EXTENSIONS constant contains Windows Media formats.
     */
    public function testAcceptedExtensionsContainsWindowsMediaFormats(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $constant = $reflection->getConstant('ACCEPTED_EXTENSIONS');

        $this->assertContains('wma', $constant);
        $this->assertContains('wmv', $constant);
    }

    /**
     * Test ACCEPTED_EXTENSIONS has exactly 7 extensions.
     */
    public function testAcceptedExtensionsCount(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $constant = $reflection->getConstant('ACCEPTED_EXTENSIONS');

        $this->assertCount(7, $constant);
    }

    /**
     * Test MAX_ITEMS constant is set to 15.
     */
    public function testMaxItemsConstant(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $constant = $reflection->getConstant('MAX_ITEMS');

        $this->assertSame(15, $constant);
    }

    /**
     * Test class cannot be instantiated (private constructor).
     */
    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $constructor = $reflection->getConstructor();

        $this->assertTrue($constructor->isPrivate());
    }

    /**
     * Test render method exists and is public static.
     */
    public function testRenderMethodIsPublicStatic(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $method = $reflection->getMethod('render');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test renderChannel method exists and is private static.
     */
    public function testRenderChannelMethodIsPrivateStatic(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $method = $reflection->getMethod('renderChannel');

        $this->assertTrue($method->isPrivate());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test renderItems method exists and is private static.
     */
    public function testRenderItemsMethodIsPrivateStatic(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $method = $reflection->getMethod('renderItems');

        $this->assertTrue($method->isPrivate());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test renderSermonMedia method exists and is private static.
     */
    public function testRenderSermonMediaMethodIsPrivateStatic(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $method = $reflection->getMethod('renderSermonMedia');

        $this->assertTrue($method->isPrivate());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test renderMediaTypeItems method exists and is private static.
     */
    public function testRenderMediaTypeItemsMethodIsPrivateStatic(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $method = $reflection->getMethod('renderMediaTypeItems');

        $this->assertTrue($method->isPrivate());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test renderItem method exists and is private static.
     */
    public function testRenderItemMethodIsPrivateStatic(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);
        $method = $reflection->getMethod('renderItem');

        $this->assertTrue($method->isPrivate());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test class is declared final.
     */
    public function testClassIsFinal(): void
    {
        $reflection = new ReflectionClass(PodcastFeed::class);

        $this->assertTrue($reflection->isFinal());
    }
}
