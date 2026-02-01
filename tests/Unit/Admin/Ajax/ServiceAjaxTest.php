<?php

/**
 * Tests for ServiceAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\ServiceAjax;
use SermonBrowser\Repositories\ServiceRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;
use RuntimeException;

/**
 * Test ServiceAjax functionality.
 */
class ServiceAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var ServiceAjax
     */
    private ServiceAjax $handler;

    /**
     * Mock service repository.
     *
     * @var \Mockery\MockInterface&ServiceRepository
     */
    private $mockRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();
        $this->mockRepo = Mockery::mock(ServiceRepository::class);
        Container::getInstance()->set(ServiceRepository::class, $this->mockRepo);

        $this->handler = new ServiceAjax();
    }

    /**
     * Test register adds correct actions.
     */
    public function testRegisterAddsCorrectActions(): void
    {
        Actions\expectAdded('wp_ajax_sb_service_create')
            ->once()
            ->with([$this->handler, 'create']);

        Actions\expectAdded('wp_ajax_sb_service_update')
            ->once()
            ->with([$this->handler, 'update']);

        Actions\expectAdded('wp_ajax_sb_service_delete')
            ->once()
            ->with([$this->handler, 'delete']);

        $this->handler->register();
        $this->addToAssertionCount(1);
    }

    /**
     * Test handler uses correct nonce action.
     */
    public function testUsesServiceNonceAction(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $nonceProperty = $reflection->getProperty('nonceAction');
        $nonceProperty->setAccessible(true);

        $this->assertSame('sb_service_nonce', $nonceProperty->getValue($this->handler));
    }

    /**
     * Test create success with name and time.
     */
    public function testCreateSuccessWithTime(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'Morning Service @ 10:30';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Morning Service @ 10:30')
            ->andReturn('Morning Service @ 10:30');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with(['name' => 'Morning Service', 'time' => '10:30'])
            ->andReturn(42);

        $this->expectJsonSuccess([
            'id' => 42,
            'name' => 'Morning Service',
            'time' => '10:30',
            'message' => 'Service created successfully.',
        ]);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test create success without time (defaults to 00:00).
     */
    public function testCreateSuccessWithoutTime(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'Special Service';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Special Service')
            ->andReturn('Special Service');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with(['name' => 'Special Service', 'time' => '00:00'])
            ->andReturn(42);

        $this->expectJsonSuccess([
            'id' => 42,
            'name' => 'Special Service',
            'time' => '00:00',
            'message' => 'Service created successfully.',
        ]);

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test create with invalid time format defaults to 00:00.
     */
    public function testCreateWithInvalidTimeFormat(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = 'Service @ invalid';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Service @ invalid')
            ->andReturn('Service @ invalid');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->with(['name' => 'Service', 'time' => '00:00'])
            ->andReturn(42);

        $this->expectJsonSuccess([
            'id' => 42,
            'name' => 'Service',
            'time' => '00:00',
            'message' => 'Service created successfully.',
        ]);

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
        $_POST['name'] = '@ 10:30';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('@ 10:30')
            ->andReturn('@ 10:30');

        $this->expectJsonError('Service name is required.', 400);

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
        $_POST['name'] = 'Test Service @ 09:00';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Test Service @ 09:00')
            ->andReturn('Test Service @ 09:00');

        $this->mockRepo->shouldReceive('create')
            ->once()
            ->andReturn(0);

        $this->expectJsonError('Failed to create service.', 400);

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
        $_POST['name'] = 'Evening Service @ 18:00';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with('Evening Service @ 18:00')
            ->andReturn('Evening Service @ 18:00');

        $this->mockRepo->shouldReceive('updateWithTimeShift')
            ->once()
            ->with(5, 'Evening Service', '18:00')
            ->andReturn(true);

        $this->expectJsonSuccess([
            'id' => 5,
            'name' => 'Evening Service',
            'time' => '18:00',
            'message' => 'Service updated successfully.',
        ]);

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
        $_POST['name'] = 'Updated Service @ 10:00';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('Updated Service @ 10:00');

        $this->expectJsonError('Invalid service ID.', 400);

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
        $_POST['name'] = '@ 10:00';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('@ 10:00');

        $this->expectJsonError('Service name is required.', 400);

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
        $_POST['name'] = 'Updated Service @ 10:00';

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->andReturn('Updated Service @ 10:00');

        $this->mockRepo->shouldReceive('updateWithTimeShift')
            ->once()
            ->andReturn(false);

        $this->expectJsonError('Failed to update service.', 400);

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

        $this->expectJsonSuccess(['id' => 5, 'message' => 'Service deleted successfully.']);

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

        $this->expectJsonError('Invalid service ID.', 400);

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

        $this->expectJsonError('Failed to delete service.', 400);

        try {
            $this->handler->delete();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Test parseServiceInput with various formats.
     *
     * @dataProvider serviceInputProvider
     */
    public function testParseServiceInput(string $input, string $expectedName, string $expectedTime): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['name'] = $input;

        $this->setupVerifyRequestSuccess();

        Functions\expect('wp_unslash')
            ->once()
            ->with($input)
            ->andReturn($input);

        // For empty name, expect error
        if (empty($expectedName)) {
            $this->expectJsonError('Service name is required.', 400);
        } else {
            $this->mockRepo->shouldReceive('create')
                ->once()
                ->with(['name' => $expectedName, 'time' => $expectedTime])
                ->andReturn(1);

            $this->expectJsonSuccess([
                'id' => 1,
                'name' => $expectedName,
                'time' => $expectedTime,
                'message' => 'Service created successfully.',
            ]);
        }

        try {
            $this->handler->create();
        } catch (RuntimeException) {
            // Expected - simulates wp_send_json termination
        }
    }

    /**
     * Data provider for parseServiceInput test.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function serviceInputProvider(): array
    {
        return [
            'name with time' => ['Morning @ 9:00', 'Morning', '9:00'],
            'name with double digit time' => ['Evening @ 18:30', 'Evening', '18:30'],
            'name without time' => ['Special', 'Special', '00:00'],
            'name with empty time' => ['Meeting @', 'Meeting', '00:00'],
            'only @ symbol' => ['@', '', '00:00'],
            'empty name with time' => ['@ 10:00', '', '10:00'],
            'name with extra spaces' => ['  Sunday Service  @  11:00  ', 'Sunday Service', '11:00'],
            'invalid time format' => ['Service @ abc', 'Service', '00:00'],
        ];
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
            ->with('valid_nonce', 'sb_service_nonce')
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
