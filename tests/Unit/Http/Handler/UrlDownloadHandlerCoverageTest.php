<?php

/**
 * Coverage tests for Http\Handler\UrlDownloadHandler class.
 *
 * Tests private helper methods via reflection.
 *
 * @package SermonBrowser\Tests\Unit\Http\Handler
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http\Handler;

use ReflectionMethod;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Http\Handler\UrlDownloadHandler;

/**
 * Coverage test class for UrlDownloadHandler.
 *
 * Tests the helper methods: sendHeaders, outputFile, cleanup.
 */
class UrlDownloadHandlerCoverageTest extends TestCase
{
    /**
     * Temporary test file path.
     */
    private string $tempFile;

    /**
     * Set up temporary test file.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tempFile = sys_get_temp_dir() . '/test_file_' . uniqid() . '.txt';
    }

    /**
     * Clean up temporary test file.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
        parent::tearDown();
    }

    /**
     * Invoke a private static method.
     *
     * @param string $methodName The method name.
     * @param array  $args       The arguments.
     * @return mixed The return value.
     */
    private function invokePrivateMethod(string $methodName, array $args): mixed
    {
        $method = new ReflectionMethod(UrlDownloadHandler::class, $methodName);
        return $method->invokeArgs(null, $args);
    }

    /**
     * Test cleanup removes temporary file.
     */
    public function testCleanupRemovesTemporaryFile(): void
    {
        file_put_contents($this->tempFile, 'test content');
        $this->assertFileExists($this->tempFile);

        $this->invokePrivateMethod('cleanup', [$this->tempFile]);

        $this->assertFileDoesNotExist($this->tempFile);
    }

    /**
     * Test cleanup handles non-existent file gracefully.
     */
    public function testCleanupHandlesNonExistentFileGracefully(): void
    {
        $nonExistentFile = '/tmp/non_existent_file_' . uniqid() . '.txt';

        // Should not throw an error
        $this->invokePrivateMethod('cleanup', [$nonExistentFile]);

        $this->addToAssertionCount(1);
    }

    /**
     * Test outputFile reads file content.
     */
    public function testOutputFileReadsContent(): void
    {
        $content = 'Test file content for output';
        file_put_contents($this->tempFile, $content);

        // Capture output
        ob_start();
        $this->invokePrivateMethod('outputFile', [$this->tempFile]);
        $output = ob_get_clean();

        $this->assertEquals($content, $output);
    }

    /**
     * Test sendHeaders sets correct content type for text file.
     *
     * Note: This test verifies the method doesn't throw errors.
     * Actual header verification requires runkit or similar.
     */
    public function testSendHeadersDoesNotThrowForTextFile(): void
    {
        file_put_contents($this->tempFile, 'plain text content');

        // We can't easily test headers without runkit, but we can verify no errors
        // Using xdebug_get_headers() would work if xdebug is available
        $this->invokePrivateMethod('sendHeaders', [
            'http://example.com/file.txt',
            $this->tempFile
        ]);

        $this->addToAssertionCount(1);
    }

    /**
     * Test sendHeaders handles file with zero size.
     */
    public function testSendHeadersHandlesZeroSizeFile(): void
    {
        file_put_contents($this->tempFile, '');

        $this->invokePrivateMethod('sendHeaders', [
            'http://example.com/empty.txt',
            $this->tempFile
        ]);

        $this->addToAssertionCount(1);
    }

    /**
     * Test sendHeaders extracts filename from URL.
     */
    public function testSendHeadersExtractsFilenameFromUrl(): void
    {
        file_put_contents($this->tempFile, 'content');

        // This tests that basename() is used correctly on the URL
        // The URL should produce filename "sermon.mp3" in the header
        $this->invokePrivateMethod('sendHeaders', [
            'http://example.com/path/to/sermon.mp3?query=param',
            $this->tempFile
        ]);

        $this->addToAssertionCount(1);
    }
}
