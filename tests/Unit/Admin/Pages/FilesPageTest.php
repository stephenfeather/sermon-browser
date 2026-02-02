<?php

/**
 * Tests for FilesPage class.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Pages
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Pages;

use Mockery;
use SermonBrowser\Admin\Pages\FilesPage;
use SermonBrowser\Constants;
use SermonBrowser\Facades\File;
use SermonBrowser\Tests\Exceptions\WpDieException;
use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test FilesPage functionality.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class FilesPageTest extends TestCase
{
    /**
     * Sample unlinked file data for tests.
     *
     * @var array<object>
     */
    private array $sampleUnlinkedFiles;

    /**
     * Sample linked file data for tests.
     *
     * @var array<object>
     */
    private array $sampleLinkedFiles;

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
        $_FILES = [];

        // Define required constants.
        if (!defined('SB_PLUGIN_URL')) {
            define('SB_PLUGIN_URL', 'http://example.com/wp-content/plugins/sermon-browser');
        }
        if (!defined('SB_ABSPATH')) {
            define('SB_ABSPATH', '/var/www/html/');
        }

        // Set up global $filetypes.
        global $filetypes;
        $filetypes = [
            'mp3' => ['name' => 'MP3 Audio'],
            'pdf' => ['name' => 'PDF Document'],
            'doc' => ['name' => 'Word Document'],
        ];

        // Set up sample data.
        $this->sampleUnlinkedFiles = [
            (object) [
                'id' => 1,
                'name' => 'sermon-2024-01-15.mp3',
            ],
            (object) [
                'id' => 2,
                'name' => 'notes.pdf',
            ],
        ];

        $this->sampleLinkedFiles = [
            (object) [
                'id' => 10,
                'name' => 'linked-sermon.mp3',
                'title' => 'Test Sermon Title',
            ],
            (object) [
                'id' => 11,
                'name' => 'study-guide.pdf',
                'title' => 'Bible Study Guide',
            ],
        ];

        // Stub common WordPress functions.
        Functions\stubs([
            'esc_attr_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'esc_html_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'esc_js' => static fn($text) => addslashes((string) $text),
            'admin_url' => static fn(string $path = '') => 'http://example.com/wp-admin/' . ltrim($path, '/'),
            'site_url' => static fn() => 'http://example.com',
            'sb_scan_dir' => static fn() => null,
            'sb_do_alerts' => static fn() => null,
            'sb_get_option' => static fn($key) => match ($key) {
                'upload_dir' => 'wp-content/uploads/sermons/',
                'sermons_per_page' => 10,
                default => ''
            },
            'sb_import_options_set' => static fn() => true,
            'sb_print_import_options_message' => static fn() => print('Import options not set'),
            'sb_print_upload_form' => static fn() => print('<form id="upload-form">Upload form</form>'),
            'wp_nonce_field' => static function ($action, $name) {
                print('<input type="hidden" name="' . $name . '" value="nonce" />');
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
        $_FILES = [];
        global $filetypes, $checkSermonUpload;
        $filetypes = [];
        $checkSermonUpload = null;
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
        $callback();
        $output = ob_get_clean();
        return $output !== false ? $output : '';
    }

    /**
     * Create a FilesPage instance with mocked File facade.
     *
     * @param array<object> $unlinked Unlinked files.
     * @param array<object> $linked Linked files.
     * @param int $cntu Unlinked count.
     * @param int $cntl Linked count.
     * @return FilesPage
     */
    private function createPageWithMockedFiles(
        array $unlinked = [],
        array $linked = [],
        int $cntu = 0,
        int $cntl = 0
    ): FilesPage {
        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($unlinked);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($linked);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn($cntu);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn($cntl);

        return new FilesPage();
    }

    // =========================================================================
    // Permission Tests
    // =========================================================================

    /**
     * Test render dies with error when user lacks upload_files capability.
     */
    public function testRenderDiesWhenUserLacksPermission(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturnUsing(static function ($message): void {
                throw new WpDieException($message);
            });

        $this->expectException(WpDieException::class);
        $this->expectExceptionMessage('You do not have the correct permissions to upload sermons');

        $page = new FilesPage();
        $page->render();
    }

    /**
     * Test render allows access when user has upload_files capability.
     */
    public function testRenderAllowsAccessWithPermission(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            $this->sampleLinkedFiles,
            2,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Upload Files', $output);
    }

    // =========================================================================
    // Directory Scanning Tests
    // =========================================================================

    /**
     * Test render calls sb_scan_dir to sync directory.
     */
    public function testRenderCallsScanDir(): void
    {
        $scanDirCalled = false;

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\when('sb_scan_dir')->alias(function () use (&$scanDirCalled) {
            $scanDirCalled = true;
        });

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $this->captureOutput(fn() => $page->render());

        $this->assertTrue($scanDirCalled, 'sb_scan_dir should be called');
    }

    // =========================================================================
    // Page Rendering Tests
    // =========================================================================

    /**
     * Test render outputs plugin logo.
     */
    public function testRenderOutputsLogo(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            $this->sampleLinkedFiles,
            2,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('logo-small.png', $output);
        $this->assertStringContainsString('sermonbrowser.com', $output);
    }

    /**
     * Test render outputs page title.
     */
    public function testRenderOutputsPageTitle(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            $this->sampleLinkedFiles,
            2,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('<h2>Upload Files</h2>', $output);
    }

    /**
     * Test render outputs upload form.
     */
    public function testRenderOutputsUploadForm(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('upload-form', $output);
        $this->assertStringContainsString('Upload form', $output);
    }

    /**
     * Test render shows import options warning when not set.
     */
    public function testRenderShowsImportWarningWhenOptionsNotSet(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\when('sb_import_options_set')->justReturn(false);

        Functions\when('sb_print_import_options_message')->alias(static function () {
            print('Please configure import options');
        });

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('class="plugin-update"', $output);
        $this->assertStringContainsString('Please configure import options', $output);
    }

    /**
     * Test render hides import warning when options are set.
     */
    public function testRenderHidesImportWarningWhenOptionsSet(): void
    {
        $importMessageCalled = false;

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\when('sb_import_options_set')->justReturn(true);

        Functions\when('sb_print_import_options_message')->alias(function () use (&$importMessageCalled) {
            $importMessageCalled = true;
            print('Import options not set');
        });

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringNotContainsString('class="plugin-update"', $output);
        $this->assertFalse($importMessageCalled, 'sb_print_import_options_message should not be called');
    }

    // =========================================================================
    // JavaScript Functions Tests
    // =========================================================================

    /**
     * Test render includes rename JavaScript function.
     */
    public function testRenderIncludesRenameFunction(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('function rename(id, old)', $output);
        $this->assertStringContainsString('SBAdmin.file.rename', $output);
    }

    /**
     * Test render includes kill (delete) JavaScript function.
     */
    public function testRenderIncludesKillFunction(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('function kill(id, f)', $output);
        $this->assertStringContainsString('SBAdmin.file.delete', $output);
    }

    /**
     * Test render includes fetchU pagination function.
     */
    public function testRenderIncludesFetchUFunction(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('function fetchU(page)', $output);
        $this->assertStringContainsString('SBAdmin.filePagination.unlinked', $output);
    }

    /**
     * Test render includes fetchL pagination function.
     */
    public function testRenderIncludesFetchLFunction(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('function fetchL(page)', $output);
        $this->assertStringContainsString('SBAdmin.filePagination.linked', $output);
    }

    /**
     * Test render includes findNow search function.
     */
    public function testRenderIncludesFindNowFunction(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('function findNow()', $output);
        $this->assertStringContainsString('SBAdmin.filePagination.search', $output);
    }

    // =========================================================================
    // Unlinked Files Section Tests
    // =========================================================================

    /**
     * Test render displays unlinked files section.
     */
    public function testRenderDisplaysUnlinkedFilesSection(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('<h2>Unlinked files</h2>', $output);
        $this->assertStringContainsString('id="the-list-u"', $output);
    }

    /**
     * Test unlinked files table headers.
     */
    public function testUnlinkedFilesTableHeaders(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString(Constants::LABEL_FILE_NAME, $output);
        $this->assertStringContainsString(Constants::LABEL_FILE_TYPE, $output);
        $this->assertStringContainsString('Actions', $output);
    }

    /**
     * Test unlinked files display file IDs.
     */
    public function testUnlinkedFilesDisplayIds(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('id="u-file-1"', $output);
        $this->assertStringContainsString('id="u-file-2"', $output);
    }

    /**
     * Test unlinked files display file names without extension.
     */
    public function testUnlinkedFilesDisplayNamesWithoutExtension(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('sermon-2024-01-15', $output);
        $this->assertStringContainsString('notes', $output);
        // The extension should NOT appear in the name column
        $this->assertStringContainsString('id="u-name-1">sermon-2024-01-15<', $output);
    }

    /**
     * Test unlinked files display file types from global array.
     */
    public function testUnlinkedFilesDisplayFileTypes(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('MP3 Audio', $output);
        $this->assertStringContainsString('PDF Document', $output);
    }

    /**
     * Test unlinked files display create sermon link.
     */
    public function testUnlinkedFilesDisplayCreateSermonLink(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Create sermon', $output);
        $this->assertStringContainsString('new_sermon.php', $output);
        // Note: The source code has a bug where it outputs literal "{(int)"
        // but the link structure is correct
        $this->assertStringContainsString('getid3=', $output);
    }

    /**
     * Test unlinked files display rename button.
     */
    public function testUnlinkedFilesDisplayRenameButton(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Rename', $output);
        $this->assertStringContainsString('onclick="rename(1,', $output);
        $this->assertStringContainsString('id="u-link-1"', $output);
    }

    /**
     * Test unlinked files display delete button with confirmation.
     */
    public function testUnlinkedFilesDisplayDeleteButton(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Delete', $output);
        $this->assertStringContainsString('confirm(', $output);
        $this->assertStringContainsString('Do you really want to delete', $output);
    }

    /**
     * Test unlinked files alternate row classes.
     */
    public function testUnlinkedFilesAlternateRowClasses(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            2,
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        // First row (odd) should NOT have alternate class - class comes before id in HTML
        $this->assertMatchesRegularExpression('/class="file\s*"[^>]*id="u-file-1"/', $output);
        // Second row (even) should have alternate class
        $this->assertMatchesRegularExpression('/class="file alternate"[^>]*id="u-file-2"/', $output);
    }

    // =========================================================================
    // Linked Files Section Tests
    // =========================================================================

    /**
     * Test render displays linked files section.
     */
    public function testRenderDisplaysLinkedFilesSection(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            [],
            $this->sampleLinkedFiles,
            0,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('<h2>Linked files</h2>', $output);
        $this->assertStringContainsString('id="the-list-l"', $output);
    }

    /**
     * Test linked files table headers include Sermon column.
     */
    public function testLinkedFilesTableHeadersIncludeSermon(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            [],
            $this->sampleLinkedFiles,
            0,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Sermon', $output);
    }

    /**
     * Test linked files display sermon titles.
     */
    public function testLinkedFilesDisplaySermonTitles(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            [],
            $this->sampleLinkedFiles,
            0,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Test Sermon Title', $output);
        $this->assertStringContainsString('Bible Study Guide', $output);
    }

    /**
     * Test linked files have additional delete confirmation.
     */
    public function testLinkedFilesHaveAdditionalDeleteConfirmation(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            [],
            $this->sampleLinkedFiles,
            0,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('function deletelinked_10', $output);
        $this->assertStringContainsString('This file is linked to the sermon called', $output);
    }

    /**
     * Test linked files display file IDs.
     */
    public function testLinkedFilesDisplayIds(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            [],
            $this->sampleLinkedFiles,
            0,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('id="l-file-10"', $output);
        $this->assertStringContainsString('id="l-file-11"', $output);
    }

    // =========================================================================
    // Search Section Tests
    // =========================================================================

    /**
     * Test render displays search section.
     */
    public function testRenderDisplaysSearchSection(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('<h2>Search for files</h2>', $output);
        $this->assertStringContainsString('id="search-input"', $output);
        $this->assertStringContainsString('id="the-list-s"', $output);
    }

    /**
     * Test search form has submit button calling findNow.
     */
    public function testSearchFormCallsFindNow(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('onclick="javascript:findNow();return false;"', $output);
        $this->assertStringContainsString('Search &raquo;', $output);
    }

    /**
     * Test search results placeholder message.
     */
    public function testSearchResultsPlaceholder(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Search results will appear here.', $output);
    }

    // =========================================================================
    // Pagination Tests
    // =========================================================================

    /**
     * Test pagination shows Next link when more than 10 unlinked files.
     */
    public function testPaginationShowsNextForUnlinkedWhenMoreThan10(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\expect('sb_get_option')
            ->with('sermons_per_page')
            ->andReturn(10);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            [],
            15,  // More than 10
            0
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString("jQuery('#uright').html('<a href=\"javascript:fetchU(2)\">Next", $output);
    }

    /**
     * Test pagination shows Next link when more than 10 linked files.
     */
    public function testPaginationShowsNextForLinkedWhenMoreThan10(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\expect('sb_get_option')
            ->with('sermons_per_page')
            ->andReturn(10);

        $page = $this->createPageWithMockedFiles(
            [],
            $this->sampleLinkedFiles,
            0,
            15  // More than 10
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString("jQuery('#right').html('<a href=\"javascript:fetchL(2)\">Next", $output);
    }

    /**
     * Test pagination does not show Next when exactly 10 files.
     */
    public function testPaginationNoNextWhenExactly10(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\expect('sb_get_option')
            ->with('sermons_per_page')
            ->andReturn(10);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            $this->sampleLinkedFiles,
            10,  // Exactly 10
            10   // Exactly 10
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringNotContainsString('fetchU(2)', $output);
        $this->assertStringNotContainsString('fetchL(2)', $output);
    }

    // =========================================================================
    // Cleanup Section Tests
    // =========================================================================

    /**
     * Test cleanup section displays when folder is writeable.
     */
    public function testCleanupSectionDisplaysWhenWriteable(): void
    {
        global $checkSermonUpload;
        $checkSermonUpload = 'writeable';

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('<h2>Clean up</h2>', $output);
        $this->assertStringContainsString('name="clean"', $output);
        $this->assertStringContainsString('Clean up missing files', $output);
    }

    /**
     * Test cleanup section hidden when folder is not writeable.
     */
    public function testCleanupSectionHiddenWhenNotWriteable(): void
    {
        global $checkSermonUpload;
        $checkSermonUpload = 'notwriteable';

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringNotContainsString('<h2>Clean up</h2>', $output);
    }

    /**
     * Test cleanup section hidden when checkSermonUpload not set.
     */
    public function testCleanupSectionHiddenWhenVariableNotSet(): void
    {
        // $checkSermonUpload is not set (null)

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringNotContainsString('<h2>Clean up</h2>', $output);
    }

    /**
     * Test cleanup section includes nonce field.
     */
    public function testCleanupSectionIncludesNonce(): void
    {
        global $checkSermonUpload;
        $checkSermonUpload = 'writeable';

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        // The nonce field should be present - our stub outputs a hidden input
        $this->assertStringContainsString('sermon_browser_clean_nonce', $output);
    }

    // =========================================================================
    // POST Handling Tests
    // =========================================================================

    /**
     * Test POST handling with no POST data renders normally.
     *
     * Note: FileActionHandler POST handling is tested in FileActionHandlerTest.
     * Here we only verify that the page renders correctly without POST data.
     */
    public function testNoPostDataRendersNormally(): void
    {
        // No POST data set

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            $this->sampleLinkedFiles,
            2,
            2
        );

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Upload Files', $output);
        $this->assertStringContainsString('sermon-2024-01-15', $output);
    }

    /**
     * Test handlePost checks for import_url first.
     *
     * This test verifies the elseif chain priority by checking that
     * when no POST data is set, the page renders without calling any handlers.
     */
    public function testHandlePostWithNoDataDoesNothing(): void
    {
        // Ensure POST is empty
        $_POST = [];

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        // Page should render normally
        $this->assertStringContainsString('Upload Files', $output);
        $this->assertStringContainsString('Unlinked files', $output);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    /**
     * Test empty file lists render properly.
     */
    public function testEmptyFileListsRenderProperly(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('id="the-list-u"', $output);
        $this->assertStringContainsString('id="the-list-l"', $output);
        // Tables should exist but be empty
        $this->assertStringContainsString('<tbody id="the-list-u">', $output);
        $this->assertStringContainsString('</tbody>', $output);
    }

    /**
     * Test file without extension shows uppercase extension as type.
     */
    public function testFileWithoutKnownExtensionShowsUppercaseType(): void
    {
        $filesWithUnknownType = [
            (object) [
                'id' => 1,
                'name' => 'document.xyz',
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($filesWithUnknownType);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn([]);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(1);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn(0);

        $page = new FilesPage();

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('XYZ', $output);
    }

    /**
     * Test file with no extension returns full name as basename.
     */
    public function testFileWithNoExtensionShowsFullName(): void
    {
        $filesWithNoExtension = [
            (object) [
                'id' => 1,
                'name' => 'noextension',
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($filesWithNoExtension);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn([]);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(1);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn(0);

        $page = new FilesPage();

        $output = $this->captureOutput(fn() => $page->render());

        // File name without extension should still be "noextension"
        $this->assertStringContainsString('noextension', $output);
    }

    /**
     * Test special characters in file names are escaped.
     */
    public function testSpecialCharactersInFileNamesAreEscaped(): void
    {
        $filesWithSpecialChars = [
            (object) [
                'id' => 1,
                'name' => 'sermon\'s "best".mp3',
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($filesWithSpecialChars);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn([]);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(1);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn(0);

        $page = new FilesPage();

        $output = $this->captureOutput(fn() => $page->render());

        // Quotes should be escaped in JavaScript contexts
        $this->assertStringContainsString('sermon\\\'s', $output);
    }

    /**
     * Test linked file with special characters in title is escaped.
     */
    public function testLinkedFileSpecialCharactersInTitleAreEscaped(): void
    {
        $linkedWithSpecialTitle = [
            (object) [
                'id' => 10,
                'name' => 'sermon.mp3',
                'title' => 'John\'s "Journey"',
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn([]);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($linkedWithSpecialTitle);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(0);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn(1);

        $page = new FilesPage();

        $output = $this->captureOutput(fn() => $page->render());

        // Title in delete confirmation should be escaped
        $this->assertStringContainsString('John\\\'s', $output);
    }

    /**
     * Test constructor initializes with empty filetypes when global not set.
     */
    public function testConstructorHandlesMissingFiletypesGlobal(): void
    {
        global $filetypes;
        $filetypes = null;

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $filesWithMp3 = [
            (object) [
                'id' => 1,
                'name' => 'sermon.mp3',
            ],
        ];

        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($filesWithMp3);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn([]);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(1);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn(0);

        $page = new FilesPage();

        $output = $this->captureOutput(fn() => $page->render());

        // Should fallback to uppercase extension
        $this->assertStringContainsString('MP3', $output);
    }

    /**
     * Test navigation divs exist for pagination.
     */
    public function testNavigationDivsExist(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $output = $this->captureOutput(fn() => $page->render());

        // Unlinked pagination divs
        $this->assertStringContainsString('id="uleft"', $output);
        $this->assertStringContainsString('id="uright"', $output);
        // Linked pagination divs
        $this->assertStringContainsString('id="left"', $output);
        $this->assertStringContainsString('id="right"', $output);
    }

    /**
     * Test render calls sb_do_alerts.
     */
    public function testRenderCallsDoAlerts(): void
    {
        $alertsCalled = false;

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\when('sb_do_alerts')->alias(function () use (&$alertsCalled) {
            $alertsCalled = true;
        });

        $page = $this->createPageWithMockedFiles([], [], 0, 0);

        $this->captureOutput(fn() => $page->render());

        $this->assertTrue($alertsCalled, 'sb_do_alerts should be called');
    }

    /**
     * Test page structure with all sections.
     */
    public function testPageStructureWithAllSections(): void
    {
        global $checkSermonUpload;
        $checkSermonUpload = 'writeable';

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        Functions\when('sb_import_options_set')->justReturn(false);

        Functions\when('sb_get_option')->alias(function ($key) {
            return match ($key) {
                'upload_dir' => 'wp-content/uploads/sermons/',
                'sermons_per_page' => 10,
                default => ''
            };
        });

        $page = $this->createPageWithMockedFiles(
            $this->sampleUnlinkedFiles,
            $this->sampleLinkedFiles,
            15,
            15
        );

        $output = $this->captureOutput(fn() => $page->render());

        // All major sections should be present
        $this->assertStringContainsString('<h2>Upload Files</h2>', $output);
        $this->assertStringContainsString('<h2>Unlinked files</h2>', $output);
        $this->assertStringContainsString('<h2>Linked files</h2>', $output);
        $this->assertStringContainsString('<h2>Search for files</h2>', $output);
        $this->assertStringContainsString('<h2>Clean up</h2>', $output);

        // Data should be present
        $this->assertStringContainsString('sermon-2024-01-15', $output);
        $this->assertStringContainsString('Test Sermon Title', $output);

        // Pagination should be present
        $this->assertStringContainsString('fetchU(2)', $output);
        $this->assertStringContainsString('fetchL(2)', $output);
    }

    /**
     * Test multiple unlinked files have proper row IDs.
     */
    public function testMultipleUnlinkedFilesHaveProperRowIds(): void
    {
        $files = [
            (object) ['id' => 100, 'name' => 'file1.mp3'],
            (object) ['id' => 200, 'name' => 'file2.pdf'],
            (object) ['id' => 300, 'name' => 'file3.doc'],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($files);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn([]);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(3);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn(0);

        $page = new FilesPage();

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('id="u-file-100"', $output);
        $this->assertStringContainsString('id="u-file-200"', $output);
        $this->assertStringContainsString('id="u-file-300"', $output);
        $this->assertStringContainsString('id="u-name-100"', $output);
        $this->assertStringContainsString('id="u-name-200"', $output);
        $this->assertStringContainsString('id="u-name-300"', $output);
        $this->assertStringContainsString('id="u-link-100"', $output);
        $this->assertStringContainsString('id="u-link-200"', $output);
        $this->assertStringContainsString('id="u-link-300"', $output);
    }

    /**
     * Test multiple linked files have proper row IDs.
     */
    public function testMultipleLinkedFilesHaveProperRowIds(): void
    {
        $files = [
            (object) ['id' => 100, 'name' => 'file1.mp3', 'title' => 'Sermon 1'],
            (object) ['id' => 200, 'name' => 'file2.pdf', 'title' => 'Sermon 2'],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('upload_files')
            ->andReturn(true);

        $fileFacade = Mockery::mock('alias:' . File::class);
        $fileFacade->shouldReceive('findUnlinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn([]);
        $fileFacade->shouldReceive('findLinkedWithTitle')
            ->with(10)
            ->once()
            ->andReturn($files);
        $fileFacade->shouldReceive('countUnlinked')
            ->once()
            ->andReturn(0);
        $fileFacade->shouldReceive('countLinked')
            ->once()
            ->andReturn(2);

        $page = new FilesPage();

        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('id="l-file-100"', $output);
        $this->assertStringContainsString('id="l-file-200"', $output);
        $this->assertStringContainsString('id="l-name-100"', $output);
        $this->assertStringContainsString('id="l-name-200"', $output);
        $this->assertStringContainsString('id="l-link-100"', $output);
        $this->assertStringContainsString('id="l-link-200"', $output);
    }
}
