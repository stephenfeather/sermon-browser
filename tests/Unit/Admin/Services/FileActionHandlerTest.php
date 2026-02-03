<?php

/**
 * Tests for FileActionHandler service.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Services
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Services;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Services\FileActionHandler;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\FileRepository;
use Brain\Monkey\Functions;
use Mockery;
use ReflectionMethod;

/**
 * Test FileActionHandler functionality.
 */
class FileActionHandlerTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var FileActionHandler
     */
    private FileActionHandler $handler;

    /**
     * Mock file repository.
     *
     * @var \Mockery\MockInterface&FileRepository
     */
    private $mockRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear OptionsManager cache before each test
        OptionsManager::clearCache();

        Container::reset();
        $this->mockRepo = Mockery::mock(FileRepository::class);
        Container::getInstance()->set(FileRepository::class, $this->mockRepo);

        $this->handler = new FileActionHandler();

        // Define SB_ABSPATH constant if not defined.
        if (!defined('SB_ABSPATH')) {
            define('SB_ABSPATH', '/var/www/html/');
        }
    }

    /**
     * Test URL import rejects invalid URL schemes.
     *
     * This test validates the SSRF protection by ensuring only HTTP/HTTPS URLs are allowed.
     */
    public function testUrlImportRejectsInvalidScheme(): void
    {
        $_POST['sb_file_import_nonce'] = 'valid_nonce';
        $_POST['url'] = 'file:///etc/passwd';
        $_POST['import_type'] = 'download';

        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('esc_url')->justReturn('file:///etc/passwd');
        Functions\when('wp_parse_url')->justReturn(['scheme' => 'file']);
        Functions\when('esc_html__')->returnArg();

        ob_start();
        $this->handler->handleUrlImport();
        $output = ob_get_clean();

        $this->assertStringContainsString('Invalid URL scheme', $output);
    }

    /**
     * Test URL import fails with invalid nonce (CSRF protection).
     */
    public function testUrlImportFailsWithInvalidNonce(): void
    {
        $_POST['sb_file_import_nonce'] = 'invalid_nonce';
        $_POST['url'] = 'https://example.com/sermon.mp3';

        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('esc_html__')->returnArg();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $this->handler->handleUrlImport();
    }

    /**
     * Test URL import fails with missing nonce.
     */
    public function testUrlImportFailsWithMissingNonce(): void
    {
        $_POST['url'] = 'https://example.com/sermon.mp3';
        // No nonce set

        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('esc_html__')->returnArg();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $this->handler->handleUrlImport();
    }

    /**
     * Test URL import handles remote fetch error.
     */
    public function testUrlImportHandlesRemoteFetchError(): void
    {
        $_POST['sb_file_import_nonce'] = 'valid_nonce';
        $_POST['url'] = 'https://example.com/sermon.mp3';
        $_POST['import_type'] = 'download';

        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('esc_url')->justReturn('https://example.com/sermon.mp3');
        Functions\when('wp_parse_url')->justReturn(['scheme' => 'https', 'host' => 'example.com']);

        $mockError = Mockery::mock('WP_Error');
        $mockError->shouldReceive('get_error_message')->andReturn('Connection timed out');

        Functions\when('wp_safe_remote_head')->justReturn($mockError);
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_html')->returnArg();

        ob_start();
        $this->handler->handleUrlImport();
        $output = ob_get_clean();

        $this->assertStringContainsString('Could not fetch URL', $output);
    }

    /**
     * Test URL import handles non-200 response.
     */
    public function testUrlImportHandlesNon200Response(): void
    {
        $_POST['sb_file_import_nonce'] = 'valid_nonce';
        $_POST['url'] = 'https://example.com/notfound.mp3';
        $_POST['import_type'] = 'download';

        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('esc_url')->justReturn('https://example.com/notfound.mp3');
        Functions\when('wp_parse_url')->justReturn(['scheme' => 'https', 'host' => 'example.com']);
        Functions\when('wp_safe_remote_head')->justReturn(['response' => ['code' => 404]]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(404);
        Functions\when('esc_html__')->returnArg();

        ob_start();
        $this->handler->handleUrlImport();
        $output = ob_get_clean();

        $this->assertStringContainsString('Invalid URL', $output);
    }

    /**
     * Verify that sanitize_file_name is called in downloadRemoteFile.
     *
     * This test uses static analysis of the source code to verify the security fix
     * for path traversal vulnerability is in place.
     */
    public function testDownloadRemoteFileContainsSanitization(): void
    {
        $sourceFile = __DIR__ . '/../../../../src/Admin/Services/FileActionHandler.php';
        $source = file_get_contents($sourceFile);

        // Verify that sanitize_file_name is called within the downloadRemoteFile method
        $this->assertStringContainsString(
            'sanitize_file_name($filename)',
            $source,
            'downloadRemoteFile must call sanitize_file_name() to prevent path traversal attacks'
        );
    }

    /**
     * Test that the security comment is present.
     *
     * The comment documents why sanitization is needed.
     */
    public function testSecurityCommentIsPresent(): void
    {
        $sourceFile = __DIR__ . '/../../../../src/Admin/Services/FileActionHandler.php';
        $source = file_get_contents($sourceFile);

        $this->assertStringContainsString(
            'prevent path traversal',
            $source,
            'Security comment should document the path traversal protection'
        );
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        Container::reset();
        parent::tearDown();
    }
}
