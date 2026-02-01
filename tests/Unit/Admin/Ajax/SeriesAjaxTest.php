<?php

/**
 * Tests for SeriesAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\SeriesAjax;
use SermonBrowser\Repositories\SeriesRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;
use RuntimeException;

/**
 * Test SeriesAjax functionality.
 */
class SeriesAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var SeriesAjax
     */
    private SeriesAjax $handler;

    /**
     * Mock series repository.
     *
     * @var \Mockery\MockInterface&SeriesRepository
     */
    private $mockRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset container and set up mock repository.
        Container::reset();
        $this->mockRepo = Mockery::mock(SeriesRepository::class);
        Container::getInstance()->set(SeriesRepository::class, $this->mockRepo);

        $this->handler = new SeriesAjax();
    }

    /**
     * Test register adds correct actions.
     */
    public function testRegisterAddsCorrectActions(): void
    {
        Actions\expectAdded('wp_ajax_sb_series_create')
            ->once()
            ->with([$this->handler, 'create']);

        Actions\expectAdded('wp_ajax_sb_series_update')
            ->once()
            ->with([$this->handler, 'update']);

        Actions\expectAdded('wp_ajax_sb_series_delete')
            ->once()
            ->with([$this->handler, 'delete']);

        $this->handler->register();
        $this->addToAssertionCount(1);
    }

    /**
     * Test handler uses correct nonce action.
     */
    public function testUsesSeriesNonceAction(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $nonceProperty = $reflection->getProperty('nonceAction');
        $nonceProperty->setAccessible(true);

        $this->assertSame('sb_series_nonce', $nonceProperty->getValue($this->handler));
    }

    /**
     * Test create success.
     */
    public function testCreateSuccess(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'Test Series';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Test Series')
            ->andReturn('Test Series');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with(['name' => 'Test Series', 'page_id' => 0])
            ->andReturn(42);

        $this->expectJsonSuccess(['id' => 42, 'name' => 'Test Series', 'message' => 'Series created successfully.']);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
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

        $this->expectJsonError('Series name is required.', 400);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test create failure from repository.
     */
    public function testCreateFailure(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'Test Series';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Test Series')
            ->andReturn('Test Series');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->andReturn(0);

        $this->expectJsonError('Failed to create series.', 400);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test update success.
     */
    public function testUpdateSuccess(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = 'Updated Series';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Updated Series')
            ->andReturn('Updated Series');

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'Updated Series'])
            ->andReturn(true);

        $this->expectJsonSuccess(['id' => 5, 'name' => 'Updated Series', 'message' => 'Series updated successfully.']);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test update with invalid ID.
     */
    public function testUpdateWithInvalidId(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '0';
        $_POST['name'] = 'Updated Series';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('Updated Series');

        $this->expectJsonError('Invalid series ID.', 400);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
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

        $this->expectJsonError('Series name is required.', 400);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test update failure from repository.
     */
    public function testUpdateFailure(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';
        $_POST['name'] = 'Updated Series';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('Updated Series');

        $this->mockRepo->shouldReceive('update')
            ->once()
            ->andReturn(false);

        $this->expectJsonError('Failed to update series.', 400);

        try {
            $this->handler->update();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
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

        $this->expectJsonSuccess(['id' => 5, 'message' => 'Series deleted successfully.']);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
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

        $this->expectJsonError('Invalid series ID.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test delete failure from repository.
     */
    public function testDeleteFailure(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['id'] = '5';

        $this->setupVerifyRequestSuccess();

        $this->mockRepo->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        $this->expectJsonError('Failed to delete series.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test create fails with invalid nonce.
     */
    public function testCreateFailsWithInvalidNonce(): void
    {
        $_POST['_sb_nonce'] = 'invalid_nonce';
        $_POST['name'] = 'Test Series';

        Functions\expect('wp_unslash')
            ->once()
            ->with('invalid_nonce')
            ->andReturn('invalid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', 'sb_series_nonce')
            ->andReturn(false);

        $this->expectJsonError('Security check failed.', 403);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test create fails without capability.
     */
    public function testCreateFailsWithoutCapability(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'Test Series';

        Functions\expect('wp_unslash')
            ->once()
            ->with('valid_nonce')
            ->andReturn('valid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sb_series_nonce')
            ->andReturn(1);

        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $this->expectJsonError('You do not have permission to perform this action.', 403);

        try {
            $this->handler->create();
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
            ->with('valid_nonce', 'sb_series_nonce')
            ->andReturn(1);

        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);
    }

    /**
     * Expect JSON success response.
     *
     * Throws exception to simulate wp_send_json_success termination.
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
     * Throws exception to simulate wp_send_json_error termination.
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
