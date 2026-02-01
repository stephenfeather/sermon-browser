<?php

/**
 * Tests for PreacherAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\PreacherAjax;
use SermonBrowser\Repositories\PreacherRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;
use RuntimeException;

/**
 * Test PreacherAjax functionality.
 */
class PreacherAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var PreacherAjax
     */
    private PreacherAjax $handler;

    /**
     * Mock preacher repository.
     *
     * @var \Mockery\MockInterface&PreacherRepository
     */
    private $mockRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();
        $this->mockRepo = Mockery::mock(PreacherRepository::class);
        Container::getInstance()->set(PreacherRepository::class, $this->mockRepo);

        $this->handler = new PreacherAjax();
    }

    /**
     * Test register adds correct actions.
     */
    public function testRegisterAddsCorrectActions(): void
    {
        Actions\expectAdded('wp_ajax_sb_preacher_create')
            ->once()
            ->with([$this->handler, 'create']);

        Actions\expectAdded('wp_ajax_sb_preacher_update')
            ->once()
            ->with([$this->handler, 'update']);

        Actions\expectAdded('wp_ajax_sb_preacher_delete')
            ->once()
            ->with([$this->handler, 'delete']);

        $this->handler->register();
        $this->addToAssertionCount(1);
    }

    /**
     * Test handler uses correct nonce action.
     */
    public function testUsesPreacherNonceAction(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $nonceProperty = $reflection->getProperty('nonceAction');
        $nonceProperty->setAccessible(true);

        $this->assertSame('sb_preacher_nonce', $nonceProperty->getValue($this->handler));
    }

    /**
     * Test create success.
     */
    public function testCreateSuccess(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'John Smith';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('John Smith')
            ->andReturn('John Smith');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with(['name' => 'John Smith', 'description' => '', 'image' => ''])
            ->andReturn(42);

        $this->expectJsonSuccess([
            'id' => 42,
            'name' => 'John Smith',
            'message' => 'Preacher created successfully.',
        ]);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test create with empty name.
     */
    public function testCreateWithEmptyName(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = '';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('')
            ->andReturn('');

        $this->expectJsonError('Preacher name is required.', 400);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test create failure from facade.
     */
    public function testCreateFailure(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'John Smith';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('John Smith');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->andReturn(0);

        $this->expectJsonError('Failed to create preacher.', 400);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test update success.
     */
    public function testUpdateSuccess(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = 'Jane Doe';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Jane Doe')
            ->andReturn('Jane Doe');

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'Jane Doe'])
            ->andReturn(true);

        $this->expectJsonSuccess([
            'id' => 5,
            'name' => 'Jane Doe',
            'message' => 'Preacher updated successfully.',
        ]);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test update with invalid ID.
     */
    public function testUpdateWithInvalidId(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '0';
        $_POST['name'] = 'Jane Doe';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('Jane Doe');

        $this->expectJsonError('Invalid preacher ID.', 400);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test update with empty name.
     */
    public function testUpdateWithEmptyName(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = '';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('');

        $this->expectJsonError('Preacher name is required.', 400);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test update failure from facade.
     */
    public function testUpdateFailure(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = 'Jane Doe';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('Jane Doe');

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $this->expectJsonError('Failed to update preacher.', 400);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test delete success.
     */
    public function testDeleteSuccess(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';

        $this->setupVerifyRequestSuccess();

        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->with(5)
            ->andReturn(true);

        $this->expectJsonSuccess([
            'id' => 5,
            'message' => 'Preacher deleted successfully.',
        ]);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test delete with invalid ID.
     */
    public function testDeleteWithInvalidId(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '0';

        $this->setupVerifyRequestSuccess();

        $this->expectJsonError('Invalid preacher ID.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test delete failure from facade.
     */
    public function testDeleteFailure(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';

        $this->setupVerifyRequestSuccess();

        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $this->expectJsonError('Failed to delete preacher.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected
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
            ->with('valid_nonce', 'sb_preacher_nonce')
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
