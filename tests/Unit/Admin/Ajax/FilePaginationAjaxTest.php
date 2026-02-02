<?php

/**
 * Tests for FilePaginationAjax handler.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\FilePaginationAjax;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Repositories\FileRepository;
use SermonBrowser\Services\Container;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use RuntimeException;

/**
 * Test FilePaginationAjax functionality.
 */
class FilePaginationAjaxTest extends TestCase
{
    /**
     * The handler under test.
     *
     * @var FilePaginationAjax
     */
    private FilePaginationAjax $handler;

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

        $this->handler = new FilePaginationAjax();
    }

    /**
     * Helper to mock get_option for OptionsManager.
     *
     * @param array<string, mixed> $options Options to return.
     */
    private function mockOptions(array $options): void
    {
        Functions\when('get_option')->alias(function ($key, $default = null) use ($options) {
            if ($key === 'sermonbrowser_options') {
                return base64_encode(serialize($options));
            }
            return $default;
        });
    }

    // =========================================================================
    // Registration Tests
    // =========================================================================

    /**
     * Test register adds all three actions.
     */
    public function testRegisterAddsAllActions(): void
    {
        Actions\expectAdded('wp_ajax_sb_file_unlinked')
            ->once()
            ->with([$this->handler, 'unlinked']);

        Actions\expectAdded('wp_ajax_sb_file_linked')
            ->once()
            ->with([$this->handler, 'linked']);

        Actions\expectAdded('wp_ajax_sb_file_search')
            ->once()
            ->with([$this->handler, 'search']);

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
     * Test handler requires edit_posts capability.
     */
    public function testRequiresEditPostsCapability(): void
    {
        $reflection = new \ReflectionClass($this->handler);
        $capabilityProperty = $reflection->getProperty('capability');
        $capabilityProperty->setAccessible(true);

        $this->assertSame('edit_posts', $capabilityProperty->getValue($this->handler));
    }

    // =========================================================================
    // Unlinked Files Tests
    // =========================================================================

    /**
     * Test unlinked returns paginated files.
     */
    public function testUnlinkedReturnsPaginatedFiles(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        $files = [
            (object) [
                'id' => 1,
                'name' => 'sermon_2024.mp3',
                'sermon_id' => null,
                'title' => null,
            ],
        ];

        $this->mockRepo->shouldReceive('findUnlinkedWithTitle')
            ->once()
            ->with(10, 0)
            ->andReturn($files);

        $this->mockRepo->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(1);

        $this->mockOptions(['filetypes' => ['mp3' => ['name' => 'MP3 Audio']]]);

        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/');

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['items'][0]['id'] === 1
                && $data['items'][0]['name'] === 'sermon_2024.mp3'
                && $data['items'][0]['is_unlinked'] === true
                && $data['total'] === 1
                && $data['page'] === 1;
        });

        try {
            $this->handler->unlinked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test unlinked includes create_sermon_url.
     */
    public function testUnlinkedIncludesCreateSermonUrl(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccessWithStubs();

        $files = [
            (object) [
                'id' => 42,
                'name' => 'test.mp3',
                'sermon_id' => null,
                'title' => null,
            ],
        ];

        $this->mockRepo->shouldReceive('findUnlinkedWithTitle')
            ->once()
            ->andReturn($files);

        $this->mockRepo->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(1);

        $this->mockOptions(['filetypes' => []]);
        Functions\when('admin_url')->alias(function ($path) {
            return 'http://example.com/wp-admin/' . $path;
        });

        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on(function ($data) {
                $url = $data['items'][0]['create_sermon_url'] ?? '';
                return strpos($url, 'getid3=42') !== false;
            }))
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_success called');
            });

        try {
            $this->handler->unlinked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test unlinked pagination offset.
     */
    public function testUnlinkedPaginationOffset(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '3';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        // Page 3, 10 per page = offset 20
        $this->mockRepo->shouldReceive('findUnlinkedWithTitle')
            ->once()
            ->with(10, 20)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(50);

        $this->mockOptions(['filetypes' => []]);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['page'] === 3 && $data['total_pages'] === 5;
        });

        try {
            $this->handler->unlinked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    // =========================================================================
    // Linked Files Tests
    // =========================================================================

    /**
     * Test linked returns paginated files.
     */
    public function testLinkedReturnsPaginatedFiles(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        $files = [
            (object) [
                'id' => 1,
                'name' => 'sermon_2024.mp3',
                'sermon_id' => 5,
                'title' => 'Sunday Sermon',
            ],
        ];

        $this->mockRepo->shouldReceive('findLinkedWithTitle')
            ->once()
            ->with(10, 0)
            ->andReturn($files);

        $this->mockRepo->shouldReceive('countLinked')
            ->once()
            ->andReturn(1);

        $this->mockOptions(['filetypes' => ['mp3' => ['name' => 'MP3 Audio']]]);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['items'][0]['id'] === 1
                && $data['items'][0]['sermon_id'] === 5
                && $data['items'][0]['sermon_title'] === 'Sunday Sermon'
                && $data['items'][0]['is_unlinked'] === false
                && $data['items'][0]['create_sermon_url'] === null;
        });

        try {
            $this->handler->linked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test linked formats file type name.
     */
    public function testLinkedFormatsFileTypeName(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        $files = [
            (object) [
                'id' => 1,
                'name' => 'video.mp4',
                'sermon_id' => 1,
                'title' => 'Test',
            ],
        ];

        $this->mockRepo->shouldReceive('findLinkedWithTitle')
            ->once()
            ->andReturn($files);

        $this->mockRepo->shouldReceive('countLinked')
            ->once()
            ->andReturn(1);

        $this->mockOptions([
            'filetypes' => ['mp4' => ['name' => 'MPEG-4 Video']],
        ]);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['items'][0]['type_name'] === 'MPEG-4 Video'
                && $data['items'][0]['extension'] === 'mp4';
        });

        try {
            $this->handler->linked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test linked falls back to uppercase extension for unknown types.
     */
    public function testLinkedFallsBackToUppercaseExtension(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccess();

        $files = [
            (object) [
                'id' => 1,
                'name' => 'file.xyz',
                'sermon_id' => 1,
                'title' => 'Test',
            ],
        ];

        $this->mockRepo->shouldReceive('findLinkedWithTitle')
            ->once()
            ->andReturn($files);

        $this->mockRepo->shouldReceive('countLinked')
            ->once()
            ->andReturn(1);

        $this->mockOptions(['filetypes' => []]);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['items'][0]['type_name'] === 'XYZ';
        });

        try {
            $this->handler->linked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    // =========================================================================
    // Search Tests
    // =========================================================================

    /**
     * Test search returns matching files.
     */
    public function testSearchReturnsMatchingFiles(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';
        $_POST['search'] = 'sermon';

        $this->setupVerifyRequestSuccessWithStubs();

        $files = [
            (object) [
                'id' => 1,
                'name' => 'sermon_01.mp3',
                'sermon_id' => 5,
                'title' => 'First Sermon',
            ],
            (object) [
                'id' => 2,
                'name' => 'sermon_02.mp3',
                'sermon_id' => null,
                'title' => null,
            ],
        ];

        $this->mockRepo->shouldReceive('searchByName')
            ->once()
            ->with('sermon', 10, 0)
            ->andReturn($files);

        $this->mockRepo->shouldReceive('countBySearch')
            ->once()
            ->with('sermon')
            ->andReturn(2);

        $this->mockOptions(['filetypes' => []]);

        $this->expectJsonSuccessCallback(function ($data) {
            return count($data['items']) === 2
                && $data['search'] === 'sermon'
                && $data['total'] === 2;
        });

        try {
            $this->handler->search();
        } catch (RuntimeException) {
            // Expected - wp_send_json_success was called
        }

        // Explicit assertion for SonarQube - Mockery expectation validates the data.
        $this->addToAssertionCount(1);
    }

    /**
     * Test search requires search term.
     */
    public function testSearchRequiresSearchTerm(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['search'] = '';

        $this->setupVerifyRequestSuccessWithStubs();

        $this->mockOptions(['sermons_per_page' => 10]);
        Functions\when('__')->returnArg(1);

        Functions\expect('wp_send_json_error')
            ->once()
            ->with(['message' => 'Search term is required.'], 400)
            ->andReturnUsing(function () {
                throw new RuntimeException('wp_send_json_error called');
            });

        try {
            $this->handler->search();
        } catch (RuntimeException) {
            // Expected - wp_send_json_error was called
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Test search pagination.
     */
    public function testSearchPagination(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '2';
        $_POST['per_page'] = '5';
        $_POST['search'] = 'audio';

        $this->setupVerifyRequestSuccessWithStubs();

        // Page 2, 5 per page = offset 5
        $this->mockRepo->shouldReceive('searchByName')
            ->once()
            ->with('audio', 5, 5)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countBySearch')
            ->once()
            ->with('audio')
            ->andReturn(12);

        $this->mockOptions(['filetypes' => []]);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['page'] === 2
                && $data['per_page'] === 5
                && $data['total_pages'] === 3
                && $data['has_prev'] === true
                && $data['has_next'] === true;
        });

        try {
            $this->handler->search();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test search with no results.
     */
    public function testSearchWithNoResults(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';
        $_POST['search'] = 'nonexistent';

        $this->setupVerifyRequestSuccessWithStubs();

        $this->mockRepo->shouldReceive('searchByName')
            ->once()
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countBySearch')
            ->once()
            ->andReturn(0);

        $this->mockOptions(['filetypes' => []]);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['items'] === []
                && $data['total'] === 0
                && $data['total_pages'] === 0;
        });

        try {
            $this->handler->search();
        } catch (RuntimeException) {
            // Expected
        }
    }

    // =========================================================================
    // Pagination Helper Tests
    // =========================================================================

    /**
     * Test default per_page from options.
     */
    public function testUsesDefaultPerPageFromOptions(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        // No per_page set

        $this->setupVerifyRequestSuccessWithStubs();

        $this->mockOptions([
            'sermons_per_page' => 25,
            'filetypes' => [],
        ]);

        $this->mockRepo->shouldReceive('findUnlinkedWithTitle')
            ->once()
            ->with(25, 0)
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(0);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['per_page'] === 25;
        });

        try {
            $this->handler->unlinked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    /**
     * Test has_next calculation.
     */
    public function testHasNextCalculation(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';
        $_POST['page'] = '1';
        $_POST['per_page'] = '10';

        $this->setupVerifyRequestSuccessWithStubs();

        $this->mockRepo->shouldReceive('findUnlinkedWithTitle')
            ->once()
            ->andReturn([]);

        $this->mockRepo->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(15); // 15 total, 10 per page = has_next true

        $this->mockOptions(['filetypes' => []]);

        $this->expectJsonSuccessCallback(function ($data) {
            return $data['has_next'] === true && $data['has_prev'] === false;
        });

        try {
            $this->handler->unlinked();
        } catch (RuntimeException) {
            // Expected
        }
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

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
            ->with('valid_nonce', 'sb_file_nonce')
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
     * Expect JSON success response with callback validation.
     *
     * @param callable $callback Validation callback.
     */
    private function expectJsonSuccessCallback(callable $callback): void
    {
        Functions\expect('wp_send_json_success')
            ->once()
            ->with(Mockery::on($callback))
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
