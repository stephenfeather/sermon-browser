<?php

/**
 * Tests for FileAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\FileAjax;
use SermonBrowser\Repositories\FileRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;
use RuntimeException;

/**
 * Test FileAjax functionality.
 */
class FileAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var FileAjax
     */
    private FileAjax $handler;

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

        Container::reset();
        $this->mockRepo = Mockery::mock(FileRepository::class);
        Container::getInstance()->set(FileRepository::class, $this->mockRepo);

        $this->handler = new FileAjax();

        // Define SB_ABSPATH constant if not defined.
        if (!defined('SB_ABSPATH')) {
            define('SB_ABSPATH', '/var/www/html/');
        }
    }

    /**
     * Test register adds correct actions.
     */
    public function testRegisterAddsCorrectActions(): void
    {
        Actions\expectAdded('wp_ajax_sb_file_rename')
            ->once()
            ->with([$this->handler, 'rename']);

        Actions\expectAdded('wp_ajax_sb_file_delete')
            ->once()
            ->with([$this->handler, 'delete']);

        $this->handler->register();
        $this->addToAssertionCount(1);
    }

    /**
     * Test handler uses correct nonce action.
     */
    public function testUsesFileNonceAction(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $nonceProperty = $reflection->getProperty('nonceAction');
        $nonceProperty->setAccessible(true);

        $this->assertSame('sb_file_nonce', $nonceProperty->getValue($this->handler));
    }

    /**
     * Test delete with invalid ID.
     */
    public function testDeleteWithInvalidId(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '0';
        $_POST['name'] = 'test.mp3';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('test.mp3')
            ->andReturn('test.mp3');

        Functions\expect('sanitize_file_name')
            ->once()
            ->with('test.mp3')
            ->andReturn('test.mp3');

        $this->expectJsonError('Invalid file ID.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test delete with empty filename.
     */
    public function testDeleteWithEmptyFilename(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = '';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('')
            ->andReturn('');

        Functions\expect('sanitize_file_name')
            ->once()
            ->with('')
            ->andReturn('');

        $this->expectJsonError('Filename is required.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test delete with invalid filename.
     */
    public function testDeleteWithInvalidFilename(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = '../../../etc/passwd';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('../../../etc/passwd')
            ->andReturn('../../../etc/passwd');

        Functions\expect('sanitize_file_name')
            ->once()
            ->andReturn('etc-passwd');

        Functions\expect('sb_get_option')
            ->once()
            ->with('upload_dir')
            ->andReturn('wp-content/uploads/sermons/');

        Functions\expect('validate_file')
            ->once()
            ->with('wp-content/uploads/sermons/etc-passwd')
            ->andReturn(1); // Invalid path

        $this->expectJsonError('Invalid filename.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test delete failure from repository.
     */
    public function testDeleteRepositoryFailure(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = 'test.mp3';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('test.mp3')
            ->andReturn('test.mp3');

        Functions\expect('sanitize_file_name')
            ->once()
            ->with('test.mp3')
            ->andReturn('test.mp3');

        Functions\expect('sb_get_option')
            ->twice()
            ->with('upload_dir')
            ->andReturn('wp-content/uploads/sermons/');

        Functions\expect('validate_file')
            ->once()
            ->andReturn(0);

        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->with(5)
            ->andReturn(false);

        $this->expectJsonError('Failed to delete file record.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test rename with invalid ID.
     */
    public function testRenameWithInvalidId(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '0';
        $_POST['name'] = 'new.mp3';
        $_POST['old_name'] = 'old.mp3';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->twice()
            ->andReturn('new.mp3', 'old.mp3');

        Functions\expect('sanitize_file_name')
            ->twice()
            ->andReturn('new.mp3', 'old.mp3');

        $this->expectJsonError('Invalid file ID.', 400);

        try {
            $this->handler->rename();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test rename with empty new filename.
     */
    public function testRenameWithEmptyNewFilename(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = '';
        $_POST['old_name'] = 'old.mp3';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->twice()
            ->andReturn('', 'old.mp3');

        Functions\expect('sanitize_file_name')
            ->twice()
            ->andReturn('', 'old.mp3');

        $this->expectJsonError('Filename is required.', 400);

        try {
            $this->handler->rename();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test rename with invalid new filename.
     */
    public function testRenameWithInvalidNewFilename(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = '../hack.mp3';
        $_POST['old_name'] = 'old.mp3';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->twice()
            ->andReturn('../hack.mp3', 'old.mp3');

        Functions\expect('sanitize_file_name')
            ->twice()
            ->andReturn('hack.mp3', 'old.mp3');

        Functions\expect('sb_get_option')
            ->once()
            ->with('upload_dir')
            ->andReturn('wp-content/uploads/sermons/');

        Functions\expect('validate_file')
            ->once()
            ->andReturn(1); // Invalid path for new name

        $this->expectJsonError('Invalid filename.', 400);

        try {
            $this->handler->rename();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test create fails with invalid nonce.
     */
    public function testRequestFailsWithInvalidNonce(): void
    {
        $_POST['_sb_nonce'] = 'invalid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = 'test.mp3';

        Functions\expect('wp_unslash')
            ->once()
            ->with('invalid_nonce')
            ->andReturn('invalid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', 'sb_file_nonce')
            ->andReturn(false);

        $this->expectJsonError('Security check failed.', 403);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test request fails without capability.
     */
    public function testRequestFailsWithoutCapability(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = 'test.mp3';

        Functions\expect('wp_unslash')
            ->once()
            ->with('valid_nonce')
            ->andReturn('valid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sb_file_nonce')
            ->andReturn(1);

        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $this->expectJsonError('You do not have permission to perform this action.', 403);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Set up mocks for successful request verification.
     */
    private function setupVerifyRequestSuccess(): void
    {
        Functions\expect('wp_unslash')
            ->once()
            ->with('valid_nonce')
            ->andReturn('valid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sb_file_nonce')
            ->andReturn(1);

        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);
    }

    /**
     * Expect JSON success response.
     *
     * @param mixed $data Expected data.
     */
    private function expectJsonSuccess(mixed $data): void
    {
        Functions\expect('wp_send_json_success')
            ->once()
            ->with($data)
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_success called');
            });
    }

    /**
     * Expect JSON error response.
     *
     * @param string $message Expected message.
     * @param int $statusCode Expected status code.
     */
    private function expectJsonError(string $message, int $statusCode): void
    {
        Functions\expect('wp_send_json_error')
            ->once()
            ->with(['message' => $message], $statusCode)
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_error called');
            });
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
