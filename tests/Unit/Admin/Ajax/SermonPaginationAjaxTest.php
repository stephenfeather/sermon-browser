<?php

/**
 * Tests for SermonPaginationAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\SermonPaginationAjax;
use SermonBrowser\Repositories\SermonRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use RuntimeException;

/**
 * Test SermonPaginationAjax functionality.
 */
class SermonPaginationAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var SermonPaginationAjax
     */
    private SermonPaginationAjax $handler;

    /**
     * Mock sermon repository.
     *
     * @var \Mockery\MockInterface&SermonRepository
     */
    private $mockRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();
        $this->mockRepo = Mockery::mock(SermonRepository::class);
        Container::getInstance()->set(SermonRepository::class, $this->mockRepo);

        $this->handler = new SermonPaginationAjax();
    }

    /**
     * Test register adds correct action for sermon list.
     */
    public function testRegisterAddsSermonListAction(): void
    {
        Actions\expectAdded('wp_ajax_sb_sermon_list')
            ->once()
            ->with([$this->handler, 'list']);

        $this->handler->register();
        $this->addToAssertionCount(1);
    }

    /**
     * Test handler uses correct nonce action.
     */
    public function testUsesSermonNonceAction(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $nonceProperty = $reflection->getProperty('nonceAction');
        $nonceProperty->setAccessible(true);

        $this->assertSame('sb_sermon_nonce', $nonceProperty->getValue($this->handler));
    }

    /**
     * Test handler requires edit_posts capability.
     */
    public function testRequiresEditPostsCapability(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $capabilityProperty = $reflection->getProperty('capability');
        $capabilityProperty->setAccessible(true);

        $this->assertSame('edit_posts', $capabilityProperty->getValue($this->handler));
    }

    /**
     * Test list returns paginated sermons.
     */
    public function testListReturnsPaginatedSermons(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccessWithStubs();

        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'Test Sermon',
                'preacher_name' => 'John Doe',
                'series_name' => 'Test Series',
                'service_name' => 'Morning Service',
                'datetime' => '2024-01-15 10:00:00',
            ],
        ];

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->with([], 10, 0)
            ->andReturn($sermons);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->with([])
            ->andReturn(1);

        Functions\when('sb_sermon_stats')->justReturn(['views' => 0]);
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/');
        Functions\when('sb_display_url')->justReturn('http://example.com/sermons');
        Functions\when('sb_query_char')->justReturn('?');
        Functions\when('wp_date')->justReturn('15 Jan 24');

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function ($data) {
                return count($data['items']) === 1
                    && $data['items'][0]['id'] === 1
                    && $data['items'][0]['title'] === 'Test Sermon'
                    && $data['total'] === 1
                    && $data['page'] === 1
                    && $data['per_page'] === 10
                    && $data['has_prev'] === false
                    && $data['has_next'] === false;
            }))
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_success called');
            });

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list with title filter.
     */
    public function testListWithTitleFilter(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';
        $_POST['title'] = 'Search Term';

        $this->setupVerifyRequestSuccessWithStubs();

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->with(['title' => 'Search Term'], 10, 0)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->with(['title' => 'Search Term'])
            ->andReturn(0);

        $this->expectJsonSuccess(Mockery::any());

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list with preacher filter.
     */
    public function testListWithPreacherFilter(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';
        $_POST['preacher'] = '5';

        $this->setupVerifyRequestSuccess();

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->with(['preacher_id' => 5], 10, 0)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->with(['preacher_id' => 5])
            ->andReturn(0);

        $this->expectJsonSuccess(Mockery::any());

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list with series filter.
     */
    public function testListWithSeriesFilter(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';
        $_POST['series'] = '3';

        $this->setupVerifyRequestSuccess();

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->with(['series_id' => 3], 10, 0)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->with(['series_id' => 3])
            ->andReturn(0);

        $this->expectJsonSuccess(Mockery::any());

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list uses default per_page from options.
     */
    public function testListUsesDefaultPerPageFromOptions(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';

        $this->setupVerifyRequestSuccess();

        Functions\expect('sb_get_option')
            ->with('sermons_per_page', 10)
            ->andReturn(25);

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->with([], 25, 0)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->andReturn(0);

        $this->expectJsonSuccess(Mockery::any());

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list pagination offset calculation.
     */
    public function testListPaginationOffset(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '3';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        // Page 3, 10 per page = offset 20
        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->with([], 10, 20)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->andReturn(50);

        $this->expectJsonSuccess(Mockery::any());

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list has_next is true when more pages exist.
     */
    public function testListHasNextWhenMorePagesExist(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->andReturn(25); // 25 total, 10 per page = 3 pages, has_next = true

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['has_next'] === true && $data['total_pages'] === 3;
            }))
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_success called');
            });

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list has_prev is true on page 2.
     */
    public function testListHasPrevOnPage2(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '2';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->andReturn(25);

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['has_prev'] === true && $data['page'] === 2;
            }))
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_success called');
            });

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test list formats unknown date correctly.
     */
    public function testListFormatsUnknownDate(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccessWithStubs();

        $sermons = [
            (object) [
                'id' => 1,
                'title' => 'Test Sermon',
                'preacher_name' => 'John Doe',
                'series_name' => '',
                'service_name' => '',
                'datetime' => '1970-01-01 00:00:00',
            ],
        ];

        $this->mockRepo->shouldReceive('findAllWithRelations')
            ->once()
            ->andReturn($sermons);

        $this->mockRepo->shouldReceive('countFiltered')
            ->once()
            ->andReturn(1);

        Functions\when('sb_sermon_stats')->justReturn([]);
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/');
        Functions\when('sb_display_url')->justReturn('http://example.com/sermons');
        Functions\when('sb_query_char')->justReturn('?');
        Functions\when('__')->returnArg(1);

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['items'][0]['formatted_date'] === 'Unknown';
            }))
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_success called');
            });

        try {
            $this->handler->list();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Set up mocks for successful request verification.
     */
    private function setupVerifyRequestSuccess(): void
    {
        Functions\expect('sanitize_text_field')
            ->with('valid_nonce')
            ->andReturn('valid_nonce');

        Functions\expect('wp_unslash')
            ->with('valid_nonce')
            ->andReturn('valid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sb_sermon_nonce')
            ->andReturn(1);

        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);
    }

    /**
     * Set up mocks for successful request verification using stubs.
     *
     * Uses when() instead of expect() to allow multiple calls to the same function.
     */
    private function setupVerifyRequestSuccessWithStubs(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_unslash')->returnArg(1);
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);
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
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        Container::reset();
        parent::tearDown();
    }
}
