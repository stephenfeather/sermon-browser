<?php

/**
 * Coverage tests for Http\Handler\FileDownloadHandler class.
 *
 * Tests the private sanitizeFilenameForHeader method via reflection.
 *
 * @package SermonBrowser\Tests\Unit\Http\Handler
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http\Handler;

use ReflectionMethod;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Http\Handler\FileDownloadHandler;

/**
 * Coverage test class for FileDownloadHandler.
 *
 * Tests the filename sanitization logic.
 */
class FileDownloadHandlerCoverageTest extends TestCase
{
    /**
     * Invoke the private sanitizeFilenameForHeader method.
     *
     * @param string $fileName The filename to sanitize.
     * @return string The sanitized filename header value.
     */
    private function invokeSanitizeFilenameForHeader(string $fileName): string
    {
        $method = new ReflectionMethod(FileDownloadHandler::class, 'sanitizeFilenameForHeader');
        return $method->invoke(null, $fileName);
    }

    /**
     * Test simple ASCII filename.
     */
    public function testSanitizeSimpleAsciiFilename(): void
    {
        $result = $this->invokeSanitizeFilenameForHeader('sermon.mp3');

        $this->assertStringContainsString('filename="sermon.mp3"', $result);
        $this->assertStringContainsString("filename*=UTF-8''sermon.mp3", $result);
    }

    /**
     * Test filename with spaces.
     */
    public function testSanitizeFilenameWithSpaces(): void
    {
        $result = $this->invokeSanitizeFilenameForHeader('my sermon file.mp3');

        // ASCII version replaces spaces with underscores
        $this->assertStringContainsString('filename="my_sermon_file.mp3"', $result);
        // UTF-8 version URL-encodes the space
        $this->assertStringContainsString("filename*=UTF-8''my%20sermon%20file.mp3", $result);
    }

    /**
     * Test filename with special characters.
     */
    public function testSanitizeFilenameWithSpecialCharacters(): void
    {
        $result = $this->invokeSanitizeFilenameForHeader('sermon (2024).mp3');

        // ASCII version replaces parens with underscores
        $this->assertStringContainsString('filename="sermon__2024_.mp3"', $result);
        // UTF-8 version URL-encodes the characters
        $this->assertStringContainsString("filename*=UTF-8''sermon%20%282024%29.mp3", $result);
    }

    /**
     * Test filename with UTF-8 characters.
     */
    public function testSanitizeFilenameWithUtf8Characters(): void
    {
        $result = $this->invokeSanitizeFilenameForHeader('prédication.mp3');

        // ASCII version replaces non-ASCII bytes with underscores (é = 2 bytes = 2 underscores)
        $this->assertStringContainsString('filename="pr__dication.mp3"', $result);
        // UTF-8 version URL-encodes the accented character
        $this->assertStringContainsString("filename*=UTF-8''pr%C3%A9dication.mp3", $result);
    }

    /**
     * Test filename with header injection attempt via newline.
     */
    public function testSanitizeFilenameStripsNewlines(): void
    {
        $maliciousName = "file.mp3\r\nX-Injected-Header: value";

        $result = $this->invokeSanitizeFilenameForHeader($maliciousName);

        // Should not contain newlines
        $this->assertStringNotContainsString("\r", $result);
        $this->assertStringNotContainsString("\n", $result);
    }

    /**
     * Test filename with tab characters.
     */
    public function testSanitizeFilenameStripsTabs(): void
    {
        $nameWithTab = "file\tname.mp3";

        $result = $this->invokeSanitizeFilenameForHeader($nameWithTab);

        // Tab should be stripped
        $this->assertStringNotContainsString("\t", $result);
        $this->assertStringContainsString('filename="filename.mp3"', $result);
    }

    /**
     * Test filename with dots and dashes (allowed characters).
     */
    public function testSanitizeFilenamePreservesDotsAndDashes(): void
    {
        $result = $this->invokeSanitizeFilenameForHeader('2024-01-15_sermon.mp3');

        $this->assertStringContainsString('filename="2024-01-15_sermon.mp3"', $result);
        $this->assertStringContainsString("filename*=UTF-8''2024-01-15_sermon.mp3", $result);
    }

    /**
     * Test empty filename.
     */
    public function testSanitizeEmptyFilename(): void
    {
        $result = $this->invokeSanitizeFilenameForHeader('');

        $this->assertStringContainsString('filename=""', $result);
        $this->assertStringContainsString("filename*=UTF-8''", $result);
    }

    /**
     * Test result format includes both filename variants.
     */
    public function testResultIncludesBothFilenameFormats(): void
    {
        $result = $this->invokeSanitizeFilenameForHeader('test.mp3');

        // Should contain standard filename
        $this->assertMatchesRegularExpression('/filename="[^"]*"/', $result);
        // Should contain RFC 5987 filename*
        $this->assertMatchesRegularExpression("/filename\\*=UTF-8''[^;]*/", $result);
    }
}
