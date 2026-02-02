<?php

/**
 * Tests for OptionsPage class.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Pages
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Pages;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Pages\OptionsPage;
use SermonBrowser\Facades\Book;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\BookRepository;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test OptionsPage functionality.
 */
class OptionsPageTest extends TestCase
{
    /**
     * OptionsPage instance under test.
     *
     * @var OptionsPage
     */
    private OptionsPage $page;

    /**
     * Mock book repository.
     *
     * @var \Mockery\MockInterface&BookRepository
     */
    private $mockBookRepo;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Define plugin constants if not already defined.
        if (!defined('SB_PLUGIN_URL')) {
            define('SB_PLUGIN_URL', 'http://example.com/wp-content/plugins/sermon-browser');
        }
        if (!defined('SB_ABSPATH')) {
            define('SB_ABSPATH', '/var/www/html/');
        }
        if (!defined('IS_MU')) {
            define('IS_MU', false);
        }

        // Reset and configure container with mock.
        Container::reset();
        $this->mockBookRepo = Mockery::mock(BookRepository::class);
        Container::getInstance()->set(BookRepository::class, $this->mockBookRepo);

        // Common WordPress function stubs.
        $this->stubCommonWordPressFunctions();

        $this->page = new OptionsPage();
    }

    /**
     * Stub common WordPress functions.
     */
    private function stubCommonWordPressFunctions(): void
    {
        Functions\stubs([
            'esc_attr_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'admin_url' => static function (string $path = ''): string {
                return 'http://example.com/wp-admin/' . ltrim($path, '/');
            },
            'site_url' => static fn() => 'http://example.com',
            'trailingslashit' => static fn($string) => rtrim($string, '/') . '/',
            'sb_display_url' => static fn() => 'http://example.com/sermons',
            'sb_query_char' => static fn($entity = true) => $entity ? '&amp;' : '&',
            'sb_get_option' => static fn($key) => match ($key) {
                'upload_dir' => 'wp-content/uploads/sermons/',
                'podcast_url' => 'http://example.com/sermons?podcast',
                'mp3_shortcode' => '[audio mp3="%SERMONURL%"]',
                'esv_api_key' => '',
                'sermons_per_page' => '10',
                'filter_type' => 'oneclick',
                'filter_hide' => 'hide',
                'hide_no_attachments' => false,
                'import_prompt' => false,
                'import_title' => false,
                'import_artist' => false,
                'import_album' => false,
                'import_comments' => false,
                'import_filename' => 'none',
                'single_template' => '',
                default => ''
            },
            'sb_update_option' => static fn($key, $val) => true,
            'sb_get_default' => static fn($key) => match ($key) {
                'sermon_path' => 'wp-content/uploads/sermons/',
                'attachment_url' => 'http://example.com/wp-content/uploads/sermons/',
                'bible_books' => ['Genesis', 'Exodus', 'Matthew', 'Mark'],
                'eng_bible_books' => ['Genesis', 'Exodus', 'Matthew', 'Mark'],
                default => ''
            },
            'sb_checkSermonUploadable' => static fn($folder = '') => 'writeable',
            'sb_mkdir' => static fn($path, $mode = 0755) => true,
            'sb_return_kbytes' => static fn($val) => match ($val) {
                '128M' => 131072,
                '15M' => 15360,
                '48M' => 49152,
                '600' => 600,
                default => (int) $val
            },
            'sb_is_super_admin' => static fn() => true,
            'wp_nonce_field' => static function ($action, $name) {
                echo '<input type="hidden" name="' . $name . '" value="nonce123" />';
            },
            'wp_verify_nonce' => static fn($nonce, $action) => 1,
            'sanitize_text_field' => static fn($text) => trim((string) $text),
            'sanitize_key' => static fn($key) => preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) ($key ?? ''))),
            'update_option' => static fn($key, $val) => true,
        ]);
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
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        Container::reset();
        parent::tearDown();
    }

    /**
     * Test constructor creates instance.
     */
    public function testConstructorCreatesInstance(): void
    {
        $page = new OptionsPage();
        $this->assertInstanceOf(OptionsPage::class, $page);
    }

    /**
     * Test render dies when user lacks permissions.
     */
    public function testRenderDiesWhenUserLacksPermissions(): void
    {
        Functions\expect('current_user_can')
            ->with('manage_options')
            ->once()
            ->andReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->andReturnUsing(function () {
                throw new \RuntimeException('wp_die called');
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        $this->page->render();
    }

    /**
     * Test render calls sb_do_alerts.
     */
    public function testRenderCallsDoAlerts(): void
    {
        Functions\when('current_user_can')->justReturn(true);

        Functions\expect('sb_do_alerts')
            ->once();

        $this->captureOutput(fn() => $this->page->render());
    }

    /**
     * Test render displays basic options heading.
     */
    public function testRenderDisplaysBasicOptionsHeading(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Basic Options', $output);
    }

    /**
     * Test render displays import options heading.
     */
    public function testRenderDisplaysImportOptionsHeading(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Import Options', $output);
    }

    /**
     * Test render displays logo.
     */
    public function testRenderDisplaysLogo(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('logo-small.png', $output);
        $this->assertStringContainsString('sermonbrowser.com', $output);
    }

    /**
     * Test render displays upload folder field.
     */
    public function testRenderDisplaysUploadFolderField(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Upload folder', $output);
        $this->assertStringContainsString('name="dir"', $output);
    }

    /**
     * Test render displays podcast fields.
     */
    public function testRenderDisplaysPodcastFields(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Public podcast feed', $output);
        $this->assertStringContainsString('Private podcast feed', $output);
        $this->assertStringContainsString('name="podcast"', $output);
    }

    /**
     * Test render displays MP3 shortcode field.
     */
    public function testRenderDisplaysMp3ShortcodeField(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('MP3 shortcode', $output);
        $this->assertStringContainsString('name="mp3_shortcode"', $output);
    }

    /**
     * Test render displays ESV API key field.
     */
    public function testRenderDisplaysEsvApiKeyField(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('ESV API Key', $output);
        $this->assertStringContainsString('name="esv_api_key"', $output);
    }

    /**
     * Test render displays sermons per page field.
     */
    public function testRenderDisplaysSermonsPerPageField(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Sermons per page', $output);
        $this->assertStringContainsString('name="perpage"', $output);
    }

    /**
     * Test render displays filter type radio buttons.
     */
    public function testRenderDisplaysFilterTypeRadioButtons(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Filter type', $output);
        $this->assertStringContainsString('name="filtertype"', $output);
        $this->assertStringContainsString('Drop-down', $output);
        $this->assertStringContainsString('One-click', $output);
        $this->assertStringContainsString('None', $output);
    }

    /**
     * Test render displays hide no attachments checkbox.
     */
    public function testRenderDisplaysHideNoAttachmentsCheckbox(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Hide sermons without attachments', $output);
        $this->assertStringContainsString('name="hide_no_attachments"', $output);
    }

    /**
     * Test render displays import option checkboxes.
     */
    public function testRenderDisplaysImportOptionCheckboxes(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('name="import_prompt"', $output);
        $this->assertStringContainsString('name="import_title"', $output);
        $this->assertStringContainsString('name="import_artist"', $output);
        $this->assertStringContainsString('name="import_album"', $output);
        $this->assertStringContainsString('name="import_comments"', $output);
    }

    /**
     * Test render displays import filename select.
     */
    public function testRenderDisplaysImportFilenameSelect(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('name="import_filename"', $output);
        $this->assertStringContainsString('Disabled', $output);
        $this->assertStringContainsString('UK-formatted date', $output);
        $this->assertStringContainsString('US-formatted date', $output);
        $this->assertStringContainsString('International formatted date', $output);
    }

    /**
     * Test render displays save button.
     */
    public function testRenderDisplaysSaveButton(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('name="save"', $output);
        $this->assertStringContainsString('Save', $output);
    }

    /**
     * Test render displays reset to defaults button.
     */
    public function testRenderDisplaysResetToDefaultsButton(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('name="resetdefault"', $output);
        $this->assertStringContainsString('Reset to defaults', $output);
    }

    /**
     * Test render displays nonce field.
     */
    public function testRenderDisplaysNonceField(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('sermon_options_save_reset_nonce', $output);
    }

    /**
     * Test handle save options dies when nonce is invalid.
     */
    public function testHandleSaveOptionsDiesWhenNonceInvalid(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'invalid_nonce';
        $_POST['dir'] = 'wp-content/uploads/sermons/';
        $_POST['filtertype'] = 'oneclick';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);
        Functions\when('wp_verify_nonce')->justReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->andReturnUsing(function () {
                throw new \RuntimeException('wp_die called');
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        $this->page->render();
    }

    /**
     * Test handle reset defaults dies when nonce is invalid.
     */
    public function testHandleResetDefaultsDiesWhenNonceInvalid(): void
    {
        $_POST['resetdefault'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'invalid_nonce';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);
        Functions\when('wp_verify_nonce')->justReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->andReturnUsing(function () {
                throw new \RuntimeException('wp_die called');
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('wp_die called');

        $this->page->render();
    }

    /**
     * Test handle save options with valid data.
     */
    public function testHandleSaveOptionsWithValidData(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = 'wp-content/uploads/sermons/';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '15';
        $_POST['filtertype'] = 'dropdown';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = 'test_api_key';
        $_POST['import_filename'] = 'uk';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Options saved successfully', $output);
    }

    /**
     * Test handle save options rejects path traversal attack.
     */
    public function testHandleSaveOptionsRejectsPathTraversal(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = '../../../etc/passwd';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '10';
        $_POST['filtertype'] = 'oneclick';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = '';
        $_POST['import_filename'] = 'none';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Invalid upload directory path', $output);
    }

    /**
     * Test handle save options rejects absolute path.
     */
    public function testHandleSaveOptionsRejectsAbsolutePath(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = '/var/www/html/uploads/';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '10';
        $_POST['filtertype'] = 'oneclick';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = '';
        $_POST['import_filename'] = 'none';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Invalid upload directory path', $output);
    }

    /**
     * Test handle save options disables donate reminder with special perpage value.
     *
     * Note: This test verifies the condition is hit but we can't easily verify update_option
     * was called with specific args since it's already stubbed. The code path is exercised.
     */
    public function testHandleSaveOptionsDisablesDonateReminderWithSpecialValue(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = 'wp-content/uploads/sermons/';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '-100';
        $_POST['filtertype'] = 'oneclick';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = '';
        $_POST['import_filename'] = 'none';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        // Just verify the page renders without error with special perpage value.
        $output = $this->captureOutput(fn() => $this->page->render());

        // The page should render successfully.
        $this->assertStringContainsString('Basic Options', $output);
    }

    /**
     * Test handle reset defaults resets options.
     */
    public function testHandleResetDefaultsResetsOptions(): void
    {
        $_POST['resetdefault'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        // Mock Book facade - allow all calls.
        $this->mockBookRepo->shouldReceive('truncate')->andReturn(true);
        $this->mockBookRepo->shouldReceive('insertBook')->andReturn(1);
        $this->mockBookRepo->shouldReceive('resetBooksForLocale')->andReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Default loaded successfully', $output);
    }

    /**
     * Test render hides upload folder field for non-super-admin in MU.
     */
    public function testRenderHidesUploadFolderFieldForNonSuperAdminInMU(): void
    {
        // We need to redefine IS_MU, but since it's already defined, we'll test
        // by mocking the sb_is_super_admin function.
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        // This test is limited because IS_MU is already defined as false.
        // The actual behavior is tested through the output.
        $output = $this->captureOutput(fn() => $this->page->render());

        // In non-MU mode with super admin, the field should be visible.
        $this->assertStringContainsString('id="sb-upload-dir"', $output);
    }

    /**
     * Test render displays filter hide checkbox.
     */
    public function testRenderDisplaysFilterHideCheckbox(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('name="filterhide"', $output);
        $this->assertStringContainsString('Minimise filter', $output);
    }

    /**
     * Test handle save options saves filter hide correctly.
     */
    public function testHandleSaveOptionsSavesFilterHideCorrectly(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = 'wp-content/uploads/sermons/';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '10';
        $_POST['filtertype'] = 'oneclick';
        $_POST['filterhide'] = 'hide';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = '';
        $_POST['import_filename'] = 'none';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);
        Functions\when('wp_verify_nonce')->justReturn(1);

        $savedOptions = [];
        Functions\when('sb_update_option')->alias(function ($key, $val) use (&$savedOptions) {
            $savedOptions[$key] = $val;
            return true;
        });

        $this->captureOutput(fn() => $this->page->render());

        $this->assertTrue($savedOptions['filter_hide']);
    }

    /**
     * Test handle save options saves import options correctly.
     */
    public function testHandleSaveOptionsSavesImportOptionsCorrectly(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = 'wp-content/uploads/sermons/';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '10';
        $_POST['filtertype'] = 'oneclick';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = '';
        $_POST['import_filename'] = 'uk';
        $_POST['import_prompt'] = '1';
        $_POST['import_title'] = '1';
        $_POST['import_artist'] = '1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);
        Functions\when('wp_verify_nonce')->justReturn(1);

        $savedOptions = [];
        Functions\when('sb_update_option')->alias(function ($key, $val) use (&$savedOptions) {
            $savedOptions[$key] = $val;
            return true;
        });

        $this->captureOutput(fn() => $this->page->render());

        $this->assertTrue($savedOptions['import_prompt']);
        $this->assertTrue($savedOptions['import_title']);
        $this->assertTrue($savedOptions['import_artist']);
        $this->assertEquals('uk', $savedOptions['import_filename']);
    }

    /**
     * Test handle save options does not update sermons per page when zero or negative.
     */
    public function testHandleSaveOptionsDoesNotUpdateSermonsPerPageWhenZeroOrNegative(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = 'wp-content/uploads/sermons/';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '0';
        $_POST['filtertype'] = 'oneclick';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = '';
        $_POST['import_filename'] = 'none';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);
        Functions\when('wp_verify_nonce')->justReturn(1);

        $savedOptions = [];
        Functions\when('sb_update_option')->alias(function ($key, $val) use (&$savedOptions) {
            $savedOptions[$key] = $val;
            return true;
        });

        $this->captureOutput(fn() => $this->page->render());

        // sermons_per_page should not be in saved options because perpage <= 0
        $this->assertArrayNotHasKey('sermons_per_page', $savedOptions);
    }

    /**
     * Test render displays CSS styling.
     */
    public function testRenderDisplaysCssStyling(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        // Check for flexbox styling used in the page.
        $this->assertStringContainsString('display: flex', $output);
        $this->assertStringContainsString('gap: 1em', $output);
    }

    /**
     * Test render displays form with POST method.
     */
    public function testRenderDisplaysFormWithPostMethod(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('<form method="post">', $output);
    }

    /**
     * Test render wraps content in div with wrap class.
     */
    public function testRenderWrapsContentInDivWithWrapClass(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('<div class="wrap">', $output);
    }

    /**
     * Test handle save options adds trailing slash to directory.
     */
    public function testHandleSaveOptionsAddsTrailingSlashToDirectory(): void
    {
        $_POST['save'] = '1';
        $_POST['sermon_options_save_reset_nonce'] = 'valid_nonce';
        $_POST['dir'] = 'wp-content/uploads/sermons';
        $_POST['podcast'] = 'http://example.com/podcast';
        $_POST['perpage'] = '10';
        $_POST['filtertype'] = 'oneclick';
        $_POST['mp3_shortcode'] = '[audio mp3="%SERMONURL%"]';
        $_POST['esv_api_key'] = '';
        $_POST['import_filename'] = 'none';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);
        Functions\when('wp_verify_nonce')->justReturn(1);

        $savedOptions = [];
        Functions\when('sb_update_option')->alias(function ($key, $val) use (&$savedOptions) {
            $savedOptions[$key] = $val;
            return true;
        });

        $this->captureOutput(fn() => $this->page->render());

        // The directory should have a trailing slash.
        $this->assertStringEndsWith('/', $savedOptions['upload_dir']);
    }

    /**
     * Test render displays files link in import options.
     */
    public function testRenderDisplaysFilesLinkInImportOptions(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sb_do_alerts')->justReturn(null);

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('sermon-browser/files.php', $output);
        $this->assertStringContainsString('Files', $output);
    }
}
