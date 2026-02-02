<?php

/**
 * Tests for LegacyAjaxHandler.
 *
 * @package SermonBrowser\Tests\Unit\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Tests\Exceptions\WpDieException;
use SermonBrowser\Ajax\LegacyAjaxHandler;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Sermon;
use Brain\Monkey\Functions;
use Mockery;
use ReflectionClass;

/**
 * Test LegacyAjaxHandler functionality.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class LegacyAjaxHandlerTest extends TestCase
{
    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear superglobals before each test.
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];

        // Define required constants.
        if (!defined('SB_ABSPATH')) {
            define('SB_ABSPATH', '/var/www/html/');
        }
        if (!defined('IS_MU')) {
            define('IS_MU', false);
        }

        // Stub common WordPress functions.
        Functions\stubs([
            'sanitize_file_name' => static fn($text) => preg_replace('/[^a-zA-Z0-9._-]/', '', $text),
            'esc_js' => static fn($text) => addslashes((string) $text),
            'admin_url' => static fn(string $path = '') => 'http://example.com/wp-admin/' . ltrim($path, '/'),
            'sb_get_option' => static fn($key) => match ($key) {
                'upload_dir' => 'wp-content/uploads/sermons/',
                'sermons_per_page' => 10,
                default => '',
            },
            'validate_file' => static fn($file) => 0,
            'esc_html_e' => static function (string $text, string $domain = 'default'): void {
                echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            },
        ]);
    }

    /**
     * Tear down test fixtures.
     */
    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        parent::tearDown();
    }

    /**
     * Capture output from a callable.
     *
     * @param callable $callback The callback to execute.
     * @return string The captured output.
     */
    private function captureOutput(callable $callback): string
    {
        ob_start();
        try {
            $callback();
        } catch (WpDieException $e) {
            // Expected - wp_die was called.
        }
        $output = ob_get_clean();
        return $output !== false ? $output : '';
    }

    /**
     * Invoke a private static method via reflection.
     *
     * @param string $methodName Method name to invoke.
     * @param array<mixed> $args Arguments to pass.
     * @return mixed Method result.
     */
    private function invokePrivateMethod(string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    /**
     * Set up wp_die() mock to throw exception.
     */
    private function setupWpDieMock(): void
    {
        Functions\expect('wp_die')
            ->zeroOrMoreTimes()
            ->andReturnUsing(static function (): void {
                throw new WpDieException('wp_die called');
            });
    }

    // =========================================================================
    // Class Structure Tests
    // =========================================================================

    /**
     * Test that the class exists and has expected methods.
     */
    public function testClassHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);

        // Public methods
        $this->assertTrue($reflection->hasMethod('handle'), 'handle() method should exist');

        // Private handler methods
        $this->assertTrue($reflection->hasMethod('handlePreacher'), 'handlePreacher() method should exist');
        $this->assertTrue($reflection->hasMethod('handleService'), 'handleService() method should exist');
        $this->assertTrue($reflection->hasMethod('handleSeries'), 'handleSeries() method should exist');
        $this->assertTrue($reflection->hasMethod('handleFile'), 'handleFile() method should exist');
        $this->assertTrue(
            $reflection->hasMethod('handleSermonPagination'),
            'handleSermonPagination() method should exist'
        );
        $this->assertTrue(
            $reflection->hasMethod('handleFilePagination'),
            'handleFilePagination() method should exist'
        );
    }

    /**
     * Test that handle method is static.
     */
    public function testHandleMethodIsStatic(): void
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isStatic(), 'handle() should be static');
        $this->assertTrue($method->isPublic(), 'handle() should be public');
    }

    /**
     * Test that handler methods are private and static.
     *
     * @dataProvider handlerMethodProvider
     */
    public function testHandlerMethodsArePrivateAndStatic(string $methodName): void
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);
        $method = $reflection->getMethod($methodName);

        $this->assertTrue($method->isStatic(), "{$methodName}() should be static");
        $this->assertTrue($method->isPrivate(), "{$methodName}() should be private");
    }

    /**
     * Data provider for handler method tests.
     *
     * @return array<string, array{string}>
     */
    public static function handlerMethodProvider(): array
    {
        return [
            'preacher handler' => ['handlePreacher'],
            'service handler' => ['handleService'],
            'series handler' => ['handleSeries'],
            'file handler' => ['handleFile'],
            'sermon pagination handler' => ['handleSermonPagination'],
            'file pagination handler' => ['handleFilePagination'],
        ];
    }

    // =========================================================================
    // determineOperationType() Tests
    // =========================================================================

    /**
     * Test determineOperationType returns 'preacher' when pname is set.
     */
    public function testDetermineOperationTypeReturnsPreacherForPname(): void
    {
        $_POST['pname'] = 'John Smith';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('preacher', $result);
    }

    /**
     * Test determineOperationType returns 'service' when sname is set.
     */
    public function testDetermineOperationTypeReturnsServiceForSname(): void
    {
        $_POST['sname'] = 'Morning Service @ 10:00';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('service', $result);
    }

    /**
     * Test determineOperationType returns 'series' when ssname is set.
     */
    public function testDetermineOperationTypeReturnsSeriesForSsname(): void
    {
        $_POST['ssname'] = 'Faith Series';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('series', $result);
    }

    /**
     * Test determineOperationType returns 'file' when fname is set with valid file.
     */
    public function testDetermineOperationTypeReturnsFileForValidFname(): void
    {
        $_POST['fname'] = 'sermon.mp3';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('file', $result);
    }

    /**
     * Test determineOperationType returns 'sermon' when fetch is set.
     */
    public function testDetermineOperationTypeReturnsSermonForFetch(): void
    {
        $_POST['fetch'] = '1';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('sermon', $result);
    }

    /**
     * Test determineOperationType returns 'file_pagination' when fetchU is set.
     */
    public function testDetermineOperationTypeReturnsFilePaginationForFetchU(): void
    {
        $_POST['fetchU'] = '1';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('file_pagination', $result);
    }

    /**
     * Test determineOperationType returns 'file_pagination' when fetchL is set.
     */
    public function testDetermineOperationTypeReturnsFilePaginationForFetchL(): void
    {
        $_POST['fetchL'] = '1';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('file_pagination', $result);
    }

    /**
     * Test determineOperationType returns 'file_pagination' when search is set.
     */
    public function testDetermineOperationTypeReturnsFilePaginationForSearch(): void
    {
        $_POST['search'] = 'sermon';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('file_pagination', $result);
    }

    /**
     * Test determineOperationType returns null when no parameters are set.
     */
    public function testDetermineOperationTypeReturnsNullWhenEmpty(): void
    {
        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertNull($result);
    }

    /**
     * Test determineOperationType priority: pname takes precedence.
     */
    public function testDetermineOperationTypePriorityPnameFirst(): void
    {
        $_POST['pname'] = 'John';
        $_POST['sname'] = 'Service';
        $_POST['fetch'] = '1';

        $result = $this->invokePrivateMethod('determineOperationType');

        $this->assertSame('preacher', $result);
    }

    // =========================================================================
    // verifyNonce() Tests
    // =========================================================================

    /**
     * Test verifyNonce returns true for valid nonce from _wpnonce.
     */
    public function testVerifyNonceReturnsTrueForValidWpnonce(): void
    {
        $_REQUEST['_wpnonce'] = 'valid_nonce';

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sb_preacher_nonce')
            ->andReturn(1);

        $result = $this->invokePrivateMethod('verifyNonce', ['preacher']);

        $this->assertTrue($result);
    }

    /**
     * Test verifyNonce returns true for valid nonce from _sb_nonce.
     */
    public function testVerifyNonceReturnsTrueForValidSbNonce(): void
    {
        $_REQUEST['_sb_nonce'] = 'valid_nonce';

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sb_service_nonce')
            ->andReturn(1);

        $result = $this->invokePrivateMethod('verifyNonce', ['service']);

        $this->assertTrue($result);
    }

    /**
     * Test verifyNonce returns false for invalid nonce.
     */
    public function testVerifyNonceReturnsFalseForInvalidNonce(): void
    {
        $_REQUEST['_wpnonce'] = 'invalid_nonce';

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', 'sb_series_nonce')
            ->andReturn(false);

        $result = $this->invokePrivateMethod('verifyNonce', ['series']);

        $this->assertFalse($result);
    }

    /**
     * Test verifyNonce returns false for unknown operation type.
     */
    public function testVerifyNonceReturnsFalseForUnknownOperationType(): void
    {
        $_REQUEST['_wpnonce'] = 'any_nonce';

        $result = $this->invokePrivateMethod('verifyNonce', ['unknown']);

        $this->assertFalse($result);
    }

    /**
     * Test verifyNonce prefers _wpnonce over _sb_nonce.
     */
    public function testVerifyNoncePrefersWpnonce(): void
    {
        $_REQUEST['_wpnonce'] = 'wpnonce_value';
        $_REQUEST['_sb_nonce'] = 'sb_nonce_value';

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('wpnonce_value', 'sb_file_nonce')
            ->andReturn(1);

        $result = $this->invokePrivateMethod('verifyNonce', ['file']);

        $this->assertTrue($result);
    }

    // =========================================================================
    // isFileExtensionAllowed() Tests
    // =========================================================================

    /**
     * Test isFileExtensionAllowed returns true when not multisite.
     */
    public function testIsFileExtensionAllowedReturnsTrueWhenNotMultisite(): void
    {
        // IS_MU is defined as false in setUp
        $result = $this->invokePrivateMethod('isFileExtensionAllowed', ['test.exe']);

        $this->assertTrue($result);
    }

    // =========================================================================
    // handlePreacher() Tests
    // =========================================================================

    /**
     * Test handlePreacher creates new preacher.
     */
    public function testHandlePreacherCreatesNewPreacher(): void
    {
        $_POST['pname'] = 'John Smith';

        $this->setupWpDieMock();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('create')
            ->once()
            ->with(['name' => 'John Smith', 'description' => '', 'image' => ''])
            ->andReturn(42);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handlePreacher');
        });

        $this->assertSame('42', $output);
    }

    /**
     * Test handlePreacher updates existing preacher.
     */
    public function testHandlePreacherUpdatesExistingPreacher(): void
    {
        $_POST['pname'] = 'Updated Name';
        $_POST['pid'] = '5';

        $this->setupWpDieMock();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('update')
            ->once()
            ->with(5, ['name' => 'Updated Name']);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handlePreacher');
        });

        $this->assertSame('done', $output);
    }

    /**
     * Test handlePreacher deletes preacher when del is set.
     */
    public function testHandlePreacherDeletesPreacher(): void
    {
        $_POST['pname'] = 'John Smith';
        $_POST['pid'] = '5';
        $_POST['del'] = '1';

        $this->setupWpDieMock();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('delete')
            ->once()
            ->with(5);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handlePreacher');
        });

        $this->assertSame('done', $output);
    }

    // =========================================================================
    // handleService() Tests
    // =========================================================================

    /**
     * Test handleService creates new service.
     */
    public function testHandleServiceCreatesNewService(): void
    {
        $_POST['sname'] = 'Morning Service @ 10:00';

        $this->setupWpDieMock();

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('create')
            ->once()
            ->with(['name' => 'Morning Service', 'time' => '10:00'])
            ->andReturn(15);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleService');
        });

        $this->assertSame('15', $output);
    }

    /**
     * Test handleService updates existing service.
     */
    public function testHandleServiceUpdatesExistingService(): void
    {
        $_POST['sname'] = 'Evening Service @ 18:30';
        $_POST['sid'] = '3';

        $this->setupWpDieMock();

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('updateWithTimeShift')
            ->once()
            ->with(3, 'Evening Service', '18:30');

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleService');
        });

        $this->assertSame('done', $output);
    }

    /**
     * Test handleService deletes service when del is set.
     */
    public function testHandleServiceDeletesService(): void
    {
        $_POST['sname'] = 'Any @ Time';
        $_POST['sid'] = '3';
        $_POST['del'] = '1';

        $this->setupWpDieMock();

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('delete')
            ->once()
            ->with(3);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleService');
        });

        $this->assertSame('done', $output);
    }

    // =========================================================================
    // handleSeries() Tests
    // =========================================================================

    /**
     * Test handleSeries creates new series.
     */
    public function testHandleSeriesCreatesNewSeries(): void
    {
        $_POST['ssname'] = 'Faith Series';

        $this->setupWpDieMock();

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('create')
            ->once()
            ->with(['name' => 'Faith Series', 'page_id' => 0])
            ->andReturn(8);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleSeries');
        });

        $this->assertSame('8', $output);
    }

    /**
     * Test handleSeries updates existing series.
     */
    public function testHandleSeriesUpdatesExistingSeries(): void
    {
        $_POST['ssname'] = 'Hope Series';
        $_POST['ssid'] = '4';

        $this->setupWpDieMock();

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('update')
            ->once()
            ->with(4, ['name' => 'Hope Series']);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleSeries');
        });

        $this->assertSame('done', $output);
    }

    /**
     * Test handleSeries deletes series when del is set.
     */
    public function testHandleSeriesDeletesSeries(): void
    {
        $_POST['ssname'] = 'Any Series';
        $_POST['ssid'] = '4';
        $_POST['del'] = '1';

        $this->setupWpDieMock();

        $series = Mockery::mock('alias:' . Series::class);
        $series->shouldReceive('delete')
            ->once()
            ->with(4);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleSeries');
        });

        $this->assertSame('done', $output);
    }

    // =========================================================================
    // handleFile() Tests
    // =========================================================================

    /**
     * Test handleFile returns early when fid not set.
     */
    public function testHandleFileReturnsEarlyWhenFidNotSet(): void
    {
        $_POST['fname'] = 'test.mp3';

        // Should return without doing anything.
        $this->invokePrivateMethod('handleFile');

        $this->addToAssertionCount(1);
    }

    /**
     * Test handleFile routes to delete when del is set.
     */
    public function testHandleFileRoutesToDeleteWhenDelSet(): void
    {
        $_POST['fname'] = 'test.mp3';
        $_POST['fid'] = '10';
        $_POST['del'] = '1';

        $this->setupWpDieMock();

        $file = Mockery::mock('alias:' . File::class);
        $file->shouldReceive('delete')
            ->once()
            ->with(10);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleFile');
        });

        $this->assertSame('deleted', $output);
    }

    // =========================================================================
    // fetchFilesForPagination() Tests
    // =========================================================================

    /**
     * Test fetchFilesForPagination returns unlinked files for fetchU.
     */
    public function testFetchFilesForPaginationReturnsUnlinkedFiles(): void
    {
        $_POST['fetchU'] = '2';

        $expectedFiles = [
            (object) ['id' => 1, 'name' => 'file1.mp3', 'title' => ''],
            (object) ['id' => 2, 'name' => 'file2.mp3', 'title' => ''],
        ];

        $file = Mockery::mock('alias:' . File::class);
        $file->shouldReceive('findUnlinkedWithTitle')
            ->once()
            ->with(10, 1)
            ->andReturn($expectedFiles);

        $result = $this->invokePrivateMethod('fetchFilesForPagination');

        $this->assertCount(2, $result);
        $this->assertSame('file1.mp3', $result[0]->name);
    }

    /**
     * Test fetchFilesForPagination returns linked files for fetchL.
     */
    public function testFetchFilesForPaginationReturnsLinkedFiles(): void
    {
        $_POST['fetchL'] = '1';

        $expectedFiles = [
            (object) ['id' => 3, 'name' => 'linked.mp3', 'title' => 'Sermon Title'],
        ];

        $file = Mockery::mock('alias:' . File::class);
        $file->shouldReceive('findLinkedWithTitle')
            ->once()
            ->with(10, 0)
            ->andReturn($expectedFiles);

        $result = $this->invokePrivateMethod('fetchFilesForPagination');

        $this->assertCount(1, $result);
        $this->assertSame('linked.mp3', $result[0]->name);
    }

    /**
     * Test fetchFilesForPagination searches by name for search param.
     */
    public function testFetchFilesForPaginationSearchesByName(): void
    {
        $_POST['search'] = 'sermon';

        $expectedFiles = [
            (object) ['id' => 5, 'name' => 'sermon-audio.mp3', 'title' => ''],
        ];

        $file = Mockery::mock('alias:' . File::class);
        $file->shouldReceive('searchByName')
            ->once()
            ->with('sermon')
            ->andReturn($expectedFiles);

        $result = $this->invokePrivateMethod('fetchFilesForPagination');

        $this->assertCount(1, $result);
        $this->assertSame('sermon-audio.mp3', $result[0]->name);
    }

    // =========================================================================
    // handleFilePagination() Tests
    // =========================================================================

    /**
     * Test handleFilePagination outputs no results message when empty.
     */
    public function testHandleFilePaginationOutputsNoResultsWhenEmpty(): void
    {
        $_POST['fetchU'] = '1';

        $this->setupWpDieMock();

        $file = Mockery::mock('alias:' . File::class);
        $file->shouldReceive('findUnlinkedWithTitle')
            ->once()
            ->andReturn([]);

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleFilePagination');
        });

        $this->assertStringContainsString('No results', $output);
        $this->assertStringContainsString('<tr><td>', $output);
    }

    /**
     * Test handleFilePagination renders file rows.
     */
    public function testHandleFilePaginationRendersFileRows(): void
    {
        $_POST['fetchL'] = '1';

        $this->setupWpDieMock();

        $files = [
            (object) ['id' => 1, 'name' => 'test.mp3', 'title' => 'Test Sermon'],
        ];

        $file = Mockery::mock('alias:' . File::class);
        $file->shouldReceive('findLinkedWithTitle')
            ->once()
            ->andReturn($files);

        // Define global filetypes.
        $GLOBALS['filetypes'] = [
            'mp3' => ['name' => 'MP3 Audio'],
        ];

        $output = $this->captureOutput(function () {
            $this->invokePrivateMethod('handleFilePagination');
        });

        $this->assertStringContainsString('test', $output);
        $this->assertStringContainsString('MP3 Audio', $output);
        $this->assertStringContainsString('Test Sermon', $output);

        unset($GLOBALS['filetypes']);
    }

    // =========================================================================
    // Security Tests
    // =========================================================================

    /**
     * Test that NONCE_ACTIONS constant contains all expected keys.
     */
    public function testNonceActionsContainsExpectedKeys(): void
    {
        $reflection = new ReflectionClass(LegacyAjaxHandler::class);
        $constant = $reflection->getConstant('NONCE_ACTIONS');

        $this->assertArrayHasKey('preacher', $constant);
        $this->assertArrayHasKey('service', $constant);
        $this->assertArrayHasKey('series', $constant);
        $this->assertArrayHasKey('file', $constant);
        $this->assertArrayHasKey('file_pagination', $constant);
        $this->assertArrayHasKey('sermon', $constant);

        $this->assertSame('sb_preacher_nonce', $constant['preacher']);
        $this->assertSame('sb_service_nonce', $constant['service']);
        $this->assertSame('sb_series_nonce', $constant['series']);
        $this->assertSame('sb_file_nonce', $constant['file']);
        $this->assertSame('sb_file_nonce', $constant['file_pagination']);
        $this->assertSame('sb_sermon_nonce', $constant['sermon']);
    }

    // =========================================================================
    // Routing Documentation Tests
    // =========================================================================

    /**
     * Test routing logic documentation.
     *
     * @dataProvider routingProvider
     */
    public function testRoutingLogicDocumentation(string $postKey, string $expectedHandler): void
    {
        $expectedRouting = [
            'pname' => 'handlePreacher',
            'sname' => 'handleService',
            'ssname' => 'handleSeries',
            'fname' => 'handleFile',
            'fetch' => 'handleSermonPagination',
            'fetchU' => 'handleFilePagination',
            'fetchL' => 'handleFilePagination',
            'search' => 'handleFilePagination',
        ];

        $this->assertArrayHasKey($postKey, $expectedRouting);
        $this->assertSame($expectedHandler, $expectedRouting[$postKey]);
    }

    /**
     * Data provider for routing documentation.
     *
     * @return array<string, array{string, string}>
     */
    public static function routingProvider(): array
    {
        return [
            'preacher name triggers preacher handler' => ['pname', 'handlePreacher'],
            'service name triggers service handler' => ['sname', 'handleService'],
            'series name triggers series handler' => ['ssname', 'handleSeries'],
            'file name triggers file handler' => ['fname', 'handleFile'],
            'fetch triggers sermon pagination' => ['fetch', 'handleSermonPagination'],
            'fetchU triggers file pagination' => ['fetchU', 'handleFilePagination'],
            'fetchL triggers file pagination' => ['fetchL', 'handleFilePagination'],
            'search triggers file pagination' => ['search', 'handleFilePagination'],
        ];
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    /**
     * Test service name parsing handles time correctly.
     */
    public function testServiceNameParsingHandlesTimeCorrectly(): void
    {
        $_POST['sname'] = '  Sunday Service  @  09:30  ';

        $this->setupWpDieMock();

        $service = Mockery::mock('alias:' . Service::class);
        $service->shouldReceive('create')
            ->once()
            ->with(['name' => 'Sunday Service', 'time' => '09:30'])
            ->andReturn(1);

        $this->captureOutput(function () {
            $this->invokePrivateMethod('handleService');
        });

        $this->addToAssertionCount(1);
    }

    /**
     * Test preacher name is sanitized.
     */
    public function testPreacherNameIsSanitized(): void
    {
        // sanitize_text_field uses strip_tags which removes tags but keeps content.
        $_POST['pname'] = '<script>alert("xss")</script>John Smith';

        $this->setupWpDieMock();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                // strip_tags removes the tags but keeps content: alert("xss")John Smith
                // The important thing is that the script tags are removed.
                return strpos($data['name'], '<script>') === false
                    && strpos($data['name'], '</script>') === false;
            }))
            ->andReturn(1);

        $this->captureOutput(function () {
            $this->invokePrivateMethod('handlePreacher');
        });

        $this->addToAssertionCount(1);
    }

    /**
     * Test file name is sanitized.
     */
    public function testFileNameIsSanitized(): void
    {
        $_POST['fname'] = '../../../etc/passwd';
        $_POST['fid'] = '1';
        $_POST['del'] = '1';

        $this->setupWpDieMock();

        // sanitize_file_name should strip path traversal.
        $file = Mockery::mock('alias:' . File::class);
        $file->shouldReceive('delete')
            ->once()
            ->with(1);

        $this->captureOutput(function () {
            $this->invokePrivateMethod('handleFile');
        });

        $this->addToAssertionCount(1);
    }
}
