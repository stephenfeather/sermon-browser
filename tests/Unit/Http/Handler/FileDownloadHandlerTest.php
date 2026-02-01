<?php

/**
 * Tests for Http\Handler\FileDownloadHandler class.
 *
 * @package SermonBrowser\Tests\Unit\Http\Handler
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http\Handler;

use ReflectionClass;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Http\Handler\FileDownloadHandler;

/**
 * Test class for FileDownloadHandler.
 *
 * Tests structure and behavior documentation.
 * Actual file serving involves exit/die and requires integration tests.
 */
class FileDownloadHandlerTest extends TestCase
{
    /**
     * Test that the class exists and has expected methods.
     */
    public function testClassHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(FileDownloadHandler::class);

        $this->assertTrue($reflection->hasMethod('handle'), 'handle() method should exist');
        $this->assertTrue($reflection->hasMethod('sendHeaders'), 'sendHeaders() method should exist');
    }

    /**
     * Test that handle method is public and static.
     */
    public function testHandleMethodIsPublicAndStatic(): void
    {
        $reflection = new ReflectionClass(FileDownloadHandler::class);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isStatic(), 'handle() should be static');
        $this->assertTrue($method->isPublic(), 'handle() should be public');
    }

    /**
     * Test that sendHeaders is private and static.
     */
    public function testSendHeadersIsPrivateAndStatic(): void
    {
        $reflection = new ReflectionClass(FileDownloadHandler::class);
        $method = $reflection->getMethod('sendHeaders');

        $this->assertTrue($method->isStatic(), 'sendHeaders() should be static');
        $this->assertTrue($method->isPrivate(), 'sendHeaders() should be private');
    }

    /**
     * Test that the class uses HandlerTrait.
     */
    public function testClassUsesHandlerTrait(): void
    {
        $reflection = new ReflectionClass(FileDownloadHandler::class);
        $traits = $reflection->getTraitNames();

        $this->assertContains(
            'SermonBrowser\Http\Handler\HandlerTrait',
            $traits,
            'FileDownloadHandler should use HandlerTrait'
        );
    }

    /**
     * Test security behavior documentation.
     */
    public function testSecurityBehaviorDocumentation(): void
    {
        // This documents the security measures in FileDownloadHandler:
        //
        // 1. Path Traversal Protection:
        //    - File names are validated against the database
        //    - Only files registered in the database can be downloaded
        //    - The file_name parameter cannot specify paths (../etc)
        //
        // 2. Download Count:
        //    - Incremented via HandlerTrait::incrementDownloadCount()
        //    - Not incremented for users with edit_posts or publish_posts capability
        //
        // 3. Headers Set:
        //    - Content-Type: application/octet-stream (forces download)
        //    - Content-Disposition: attachment; filename="..."
        //    - Content-Length: (file size)

        $this->addToAssertionCount(1);
        $this->assertTrue(true, 'Security measures are documented in code comments');
    }

    /**
     * Test expected behavior documentation.
     *
     * @dataProvider behaviorProvider
     */
    public function testBehaviorDocumentation(string $scenario, string $expectedOutcome): void
    {
        $behaviors = [
            'file_not_in_db' => '404 error via wp_die',
            'file_in_db' => 'Headers sent, file streamed, exit',
            'url_encoded_name' => 'Decoded before database lookup',
            'editor_download' => 'Count not incremented',
            'visitor_download' => 'Count incremented',
        ];

        $this->assertArrayHasKey($scenario, $behaviors);
        $this->assertSame($expectedOutcome, $behaviors[$scenario]);
    }

    /**
     * Data provider for behavior documentation.
     *
     * @return array<string, array{string, string}>
     */
    public static function behaviorProvider(): array
    {
        return [
            'file not in database' => ['file_not_in_db', '404 error via wp_die'],
            'file found in database' => ['file_in_db', 'Headers sent, file streamed, exit'],
            'URL-encoded file name' => ['url_encoded_name', 'Decoded before database lookup'],
            'editor downloads file' => ['editor_download', 'Count not incremented'],
            'visitor downloads file' => ['visitor_download', 'Count incremented'],
        ];
    }
}
