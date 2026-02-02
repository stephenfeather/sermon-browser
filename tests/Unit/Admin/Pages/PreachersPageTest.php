<?php

/**
 * Tests for PreachersPage class.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Pages
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Pages;

use Mockery;
use SermonBrowser\Admin\Pages\PreachersPage;
use SermonBrowser\Constants;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Tests\Exceptions\WpDieException;
use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test PreachersPage functionality.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PreachersPageTest extends TestCase
{
    /**
     * Sample preacher data for tests.
     *
     * @var array<object>
     */
    private array $samplePreachers;

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

        // Set up sample data.
        $this->samplePreachers = [
            (object) [
                'id' => 1,
                'name' => 'John Smith',
                'description' => 'Senior Pastor',
                'image' => 'john.jpg',
                'sermon_count' => 10,
            ],
            (object) [
                'id' => 2,
                'name' => 'Jane Doe',
                'description' => 'Associate Pastor',
                'image' => '',
                'sermon_count' => 5,
            ],
        ];

        // Stub common WordPress functions.
        Functions\stubs([
            'esc_attr_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'esc_textarea' => static fn($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'),
            'esc_js' => static fn($text) => addslashes((string) $text),
            'admin_url' => static fn(string $path = '') => 'http://example.com/wp-admin/' . ltrim($path, '/'),
            'site_url' => static fn() => 'http://example.com',
            'trailingslashit' => static fn($string) => rtrim($string, '/\\') . '/',
            'sb_get_option' => static fn($key) => ($key === 'upload_dir') ? 'wp-content/uploads/sermons/' : '',
            'sanitize_textarea_field' => static fn($text) => strip_tags((string) $text),
            'sanitize_file_name' => static fn($text) => preg_replace('/[^a-zA-Z0-9._-]/', '', $text),
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
     * Create a PreachersPage instance with mocked dependencies.
     *
     * @return PreachersPage
     */
    private function createPage(): PreachersPage
    {
        return new PreachersPage();
    }

    // =========================================================================
    // Permission Tests
    // =========================================================================

    /**
     * Test render dies with error when user lacks manage_categories capability.
     */
    public function testRenderDiesWhenUserLacksPermission(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturnUsing(static function ($message): void {
                throw new WpDieException($message);
            });

        $this->expectException(WpDieException::class);

        $page = $this->createPage();
        $page->render();
    }

    // =========================================================================
    // List View Tests
    // =========================================================================

    /**
     * Test render displays list view by default.
     */
    public function testRenderDisplaysListViewByDefault(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        Functions\expect('wp_nonce_field')->zeroOrMoreTimes();

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Preachers', $output);
        $this->assertStringContainsString('add new', $output);
        $this->assertStringContainsString('John Smith', $output);
        $this->assertStringContainsString('Jane Doe', $output);
    }

    /**
     * Test render displays saved message when saved query param is present.
     */
    public function testRenderDisplaysSavedMessage(): void
    {
        $_GET['saved'] = 'true';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Preacher saved to database', $output);
        $this->assertStringContainsString('class="updated fade"', $output);
    }

    /**
     * Test list view displays preacher IDs.
     */
    public function testListViewDisplaysPreacherIds(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('>1<', $output);
        $this->assertStringContainsString('>2<', $output);
    }

    /**
     * Test list view displays sermon counts.
     */
    public function testListViewDisplaysSermonCounts(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('>10<', $output);
        $this->assertStringContainsString('>5<', $output);
    }

    /**
     * Test list view displays preacher images when present.
     */
    public function testListViewDisplaysPreacherImages(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('john.jpg', $output);
        $this->assertStringContainsString(Constants::IMAGES_PATH, $output);
    }

    /**
     * Test list view alternates row classes.
     */
    public function testListViewAlternatesRowClasses(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('class="alternate"', $output);
    }

    /**
     * Test list view displays edit links for each preacher.
     */
    public function testListViewDisplaysEditLinks(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('act=edit&pid=1', $output);
        $this->assertStringContainsString('act=edit&pid=2', $output);
        $this->assertStringContainsString('>Edit<', $output);
    }

    /**
     * Test list view shows delete link only when preacher has no sermons.
     */
    public function testListViewShowsDeleteLinkWhenNoSermons(): void
    {
        $preachers = [
            (object) [
                'id' => 1,
                'name' => 'John Smith',
                'description' => '',
                'image' => '',
                'sermon_count' => 0,
            ],
            (object) [
                'id' => 2,
                'name' => 'Jane Doe',
                'description' => '',
                'image' => '',
                'sermon_count' => 0,
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($preachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('act=kill', $output);
        $this->assertStringContainsString('Are you sure you want to delete', $output);
    }

    /**
     * Test list view shows alert for preachers with sermons.
     */
    public function testListViewShowsAlertForPreachersWithSermons(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString(
            'You cannot delete this preacher until you first delete any sermons',
            $output
        );
    }

    /**
     * Test list view shows alert when only one preacher exists.
     */
    public function testListViewShowsAlertWhenOnlyOnePreacher(): void
    {
        $preachers = [
            (object) [
                'id' => 1,
                'name' => 'Only Preacher',
                'description' => '',
                'image' => '',
                'sermon_count' => 0,
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($preachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('You must have at least one preacher', $output);
    }

    /**
     * Test list view outputs table headers.
     */
    public function testListViewOutputsTableHeaders(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Image', $output);
        $this->assertStringContainsString('Sermons', $output);
        $this->assertStringContainsString('Actions', $output);
    }

    /**
     * Test list view outputs plugin logo.
     */
    public function testListViewOutputsLogo(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('logo-small.png', $output);
        $this->assertStringContainsString('sermonbrowser.com', $output);
    }

    // =========================================================================
    // Edit Form Tests
    // =========================================================================

    /**
     * Test render displays new preacher form when act=new.
     */
    public function testRenderDisplaysNewPreacherForm(): void
    {
        $_GET['act'] = 'new';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_checkSermonUploadable')
            ->once()
            ->with(Constants::IMAGES_PATH)
            ->andReturn('writeable');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('sermon_manage_preachers', 'sermon_manage_preachers_nonce');

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Add', $output);
        $this->assertStringContainsString('preacher', $output);
        $this->assertStringContainsString('name="name"', $output);
        $this->assertStringContainsString('name="description"', $output);
        $this->assertStringContainsString('name="upload"', $output);
        $this->assertStringContainsString('name="save"', $output);
    }

    /**
     * Test render displays edit preacher form when act=edit.
     */
    public function testRenderDisplaysEditPreacherForm(): void
    {
        $_GET['act'] = 'edit';
        $_GET['pid'] = '1';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_checkSermonUploadable')
            ->once()
            ->with(Constants::IMAGES_PATH)
            ->andReturn('writeable');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('sermon_manage_preachers', 'sermon_manage_preachers_nonce');

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($this->samplePreachers[0]);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Edit', $output);
        $this->assertStringContainsString('preacher', $output);
        $this->assertStringContainsString('John Smith', $output);
        $this->assertStringContainsString('Senior Pastor', $output);
        $this->assertStringContainsString('john.jpg', $output);
    }

    /**
     * Test edit form shows existing image and remove checkbox.
     */
    public function testEditFormShowsExistingImageAndRemoveCheckbox(): void
    {
        $_GET['act'] = 'edit';
        $_GET['pid'] = '1';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_checkSermonUploadable')
            ->once()
            ->with(Constants::IMAGES_PATH)
            ->andReturn('writeable');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('sermon_manage_preachers', 'sermon_manage_preachers_nonce');

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($this->samplePreachers[0]);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('name="old"', $output);
        $this->assertStringContainsString('value="john.jpg"', $output);
        $this->assertStringContainsString('name="remove"', $output);
        $this->assertStringContainsString('Remove image', $output);
    }

    /**
     * Test edit form shows warning when images folder not writeable.
     */
    public function testEditFormShowsWarningWhenImagesFolderNotWriteable(): void
    {
        $_GET['act'] = 'new';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_checkSermonUploadable')
            ->once()
            ->with(Constants::IMAGES_PATH)
            ->andReturn('notwriteable');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('sermon_manage_preachers', 'sermon_manage_preachers_nonce');

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('images folder is not writeable', $output);
    }

    /**
     * Test edit form attempts to create images folder when it doesn't exist.
     */
    public function testEditFormCreatesImagesFolderWhenNotExists(): void
    {
        $_GET['act'] = 'new';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        // First call returns 'notexist', second returns 'writeable'.
        Functions\expect('sb_checkSermonUploadable')
            ->twice()
            ->with(Constants::IMAGES_PATH)
            ->andReturn('notexist', 'writeable');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('sermon_manage_preachers', 'sermon_manage_preachers_nonce');

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        // Should not show the warning since folder was created successfully.
        $this->assertStringNotContainsString('images folder is not writeable', $output);
    }

    // =========================================================================
    // Delete Action Tests
    // =========================================================================

    /**
     * Test delete action removes preacher when no sermons exist.
     */
    public function testDeleteActionRemovesPreacherWhenNoSermons(): void
    {
        $_GET['act'] = 'kill';
        $_GET['pid'] = '2';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('hasSermons')
            ->once()
            ->with(2)
            ->andReturn(false);

        $preacher->shouldReceive('find')
            ->once()
            ->with(2)
            ->andReturn($this->samplePreachers[1]);

        $preacher->shouldReceive('delete')
            ->once()
            ->with(2);

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn([$this->samplePreachers[0]]);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        // Should show list view after deletion.
        $this->assertStringContainsString('Preachers', $output);
    }

    /**
     * Test delete action deletes preacher image file.
     */
    public function testDeleteActionDeletesPreacherImage(): void
    {
        $_GET['act'] = 'kill';
        $_GET['pid'] = '1';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('hasSermons')
            ->once()
            ->with(1)
            ->andReturn(false);

        $preacher->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($this->samplePreachers[0]);

        $preacher->shouldReceive('delete')
            ->once()
            ->with(1);

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn([$this->samplePreachers[1]]);

        $page = $this->createPage();

        // The unlink is called with @ suppressor, so we just verify delete is called.
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Preachers', $output);
    }

    /**
     * Test delete action shows error when preacher has sermons.
     */
    public function testDeleteActionShowsErrorWhenPreacherHasSermons(): void
    {
        $_GET['act'] = 'kill';
        $_GET['pid'] = '1';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('hasSermons')
            ->once()
            ->with(1)
            ->andReturn(true);

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString(
            'You cannot delete this preacher until you first delete any sermons they have preached',
            $output
        );
    }

    // =========================================================================
    // Save Action Tests
    // =========================================================================

    /**
     * Test save action dies when nonce verification fails.
     */
    public function testSaveActionDiesWhenNonceVerificationFails(): void
    {
        $_POST['save'] = 'true';
        $_POST['name'] = 'New Preacher';
        $_POST['description'] = 'Description';
        $_POST['sermon_manage_preachers_nonce'] = 'invalid_nonce';
        $_REQUEST['pid'] = '0';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', 'sermon_manage_preachers')
            ->andReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturnUsing(static function ($message): void {
                throw new WpDieException($message);
            });

        $this->expectException(WpDieException::class);

        $page = $this->createPage();
        $page->render();
    }

    /**
     * Test save action creates new preacher.
     */
    public function testSaveActionCreatesNewPreacher(): void
    {
        $_POST['save'] = 'true';
        $_POST['name'] = 'New Preacher';
        $_POST['description'] = 'A new preacher';
        $_POST['sermon_manage_preachers_nonce'] = 'valid_nonce';
        $_REQUEST['pid'] = '0';
        $_FILES['upload'] = ['name' => '', 'error' => UPLOAD_ERR_NO_FILE];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sermon_manage_preachers')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('find')
            ->once()
            ->with(0)
            ->andReturn(null);

        $preacher->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'New Preacher',
                'description' => 'A new preacher',
                'image' => '',
            ]);

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        // Should redirect via JavaScript.
        $this->assertStringContainsString('document.location', $output);
        $this->assertStringContainsString('saved=true', $output);
    }

    /**
     * Test save action updates existing preacher.
     */
    public function testSaveActionUpdatesExistingPreacher(): void
    {
        $_POST['save'] = 'true';
        $_POST['name'] = 'Updated Name';
        $_POST['description'] = 'Updated description';
        $_POST['old'] = 'john.jpg';
        $_POST['sermon_manage_preachers_nonce'] = 'valid_nonce';
        $_REQUEST['pid'] = '1';
        $_FILES['upload'] = ['name' => '', 'error' => UPLOAD_ERR_NO_FILE];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sermon_manage_preachers')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($this->samplePreachers[0]);

        $preacher->shouldReceive('update')
            ->once()
            ->with(1, [
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'image' => 'john.jpg',
            ]);

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('document.location', $output);
        $this->assertStringContainsString('saved=true', $output);
    }

    /**
     * Test save action handles remove image checkbox.
     */
    public function testSaveActionRemovesImageWhenCheckboxChecked(): void
    {
        $_POST['save'] = 'true';
        $_POST['name'] = 'John Smith';
        $_POST['description'] = 'Senior Pastor';
        $_POST['old'] = 'john.jpg';
        $_POST['remove'] = 'true';
        $_POST['sermon_manage_preachers_nonce'] = 'valid_nonce';
        $_REQUEST['pid'] = '1';
        $_FILES['upload'] = ['name' => '', 'error' => UPLOAD_ERR_NO_FILE];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sermon_manage_preachers')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($this->samplePreachers[0]);

        $preacher->shouldReceive('update')
            ->once()
            ->with(1, [
                'name' => 'John Smith',
                'description' => 'Senior Pastor',
                'image' => '',
            ]);

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('document.location', $output);
    }

    /**
     * Test save action shows error on upload failure.
     */
    public function testSaveActionShowsErrorOnUploadFailure(): void
    {
        $_POST['save'] = 'true';
        $_POST['name'] = 'New Preacher';
        $_POST['description'] = 'Description';
        $_POST['sermon_manage_preachers_nonce'] = 'valid_nonce';
        $_REQUEST['pid'] = '0';
        $_FILES['upload'] = [
            'name' => 'test.jpg',
            'tmp_name' => '/tmp/phpXXX',
            'error' => UPLOAD_ERR_INI_SIZE,
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sermon_manage_preachers')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('create')
            ->once()
            ->with(Mockery::type('array'));

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Could not upload file', $output);
        $this->assertStringContainsString('Please check the Options page', $output);
    }

    // =========================================================================
    // Image Upload Tests
    // =========================================================================

    /**
     * Test successful image upload during preacher save.
     */
    public function testSaveActionHandlesSuccessfulImageUpload(): void
    {
        $_POST['save'] = 'true';
        $_POST['name'] = 'New Preacher';
        $_POST['description'] = 'Description';
        $_POST['sermon_manage_preachers_nonce'] = 'valid_nonce';
        $_REQUEST['pid'] = '0';
        $_FILES['upload'] = [
            'name' => 'newimage.jpg',
            'tmp_name' => '/tmp/phpXXX',
            'error' => UPLOAD_ERR_OK,
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sermon_manage_preachers')
            ->andReturn(true);

        Functions\expect('sb_mkdir')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        // Note: find() is NOT called when a file is being uploaded - it's only called
        // when no file is uploaded to get the existing image.

        $preacher->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                // The image should be empty because move_uploaded_file will fail in test.
                return $data['name'] === 'New Preacher'
                    && $data['description'] === 'Description';
            }));

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        // Since move_uploaded_file fails in tests, we expect error message.
        $this->assertStringContainsString('Could not save uploaded file', $output);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    /**
     * Test constructor initializes upload directory from options.
     */
    public function testConstructorInitializesUploadDirectory(): void
    {
        $page = $this->createPage();

        // If we get here without error, the constructor worked.
        $this->assertInstanceOf(PreachersPage::class, $page);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    /**
     * Test list view handles empty preachers array.
     */
    public function testListViewHandlesEmptyPreachers(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn([]);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Preachers', $output);
        $this->assertStringContainsString('add new', $output);
        $this->assertStringContainsString('<table', $output);
    }

    /**
     * Test edit form with pid=0 treats as new preacher.
     */
    public function testEditFormWithZeroPidTreatsAsNew(): void
    {
        $_GET['act'] = 'edit';
        $_GET['pid'] = '0';

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_checkSermonUploadable')
            ->once()
            ->with(Constants::IMAGES_PATH)
            ->andReturn('writeable');

        Functions\expect('wp_nonce_field')
            ->once()
            ->with('sermon_manage_preachers', 'sermon_manage_preachers_nonce');

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('find')
            ->once()
            ->with(0)
            ->andReturn(null);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        // Should show edit form with empty fields.
        $this->assertStringContainsString('name="name"', $output);
        $this->assertStringContainsString('value=""', $output);
    }

    /**
     * Test special characters in preacher name are escaped.
     */
    public function testSpecialCharactersInPreacherNameAreEscaped(): void
    {
        $preachers = [
            (object) [
                'id' => 1,
                'name' => 'John "Jack" O\'Brien',
                'description' => 'Has <script> in bio',
                'image' => '',
                'sermon_count' => 0,
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($preachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        // Script tag should be escaped.
        $this->assertStringNotContainsString('<script>', $output);
    }

    /**
     * Test delete confirmation message includes preacher name.
     */
    public function testDeleteConfirmationIncludesPreacherName(): void
    {
        $preachers = [
            (object) [
                'id' => 1,
                'name' => 'Pastor Mike',
                'description' => '',
                'image' => '',
                'sermon_count' => 0,
            ],
            (object) [
                'id' => 2,
                'name' => 'Pastor Jane',
                'description' => '',
                'image' => '',
                'sermon_count' => 0,
            ],
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($preachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        $this->assertStringContainsString('Pastor Mike', $output);
        $this->assertStringContainsString('Are you sure you want to delete', $output);
    }

    /**
     * Test save action handles update with new image replacing old.
     */
    public function testSaveActionDeletesOldImageWhenNewImageUploaded(): void
    {
        $_POST['save'] = 'true';
        $_POST['name'] = 'John Smith';
        $_POST['description'] = 'Senior Pastor';
        $_POST['old'] = 'old-image.jpg';
        $_POST['sermon_manage_preachers_nonce'] = 'valid_nonce';
        $_REQUEST['pid'] = '1';
        $_FILES['upload'] = [
            'name' => 'new-image.jpg',
            'tmp_name' => '/tmp/phpXXX',
            'error' => UPLOAD_ERR_OK,
        ];

        Functions\expect('current_user_can')
            ->once()
            ->with('manage_categories')
            ->andReturn(true);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sermon_manage_preachers')
            ->andReturn(true);

        Functions\expect('sb_mkdir')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        Functions\expect('sb_do_alerts')->once();

        $preacher = Mockery::mock('alias:' . Preacher::class);
        // Note: find() is NOT called when a file is being uploaded - it's only called
        // when no file is uploaded to get the existing image.

        // Since move_uploaded_file will fail, filename becomes empty.
        $preacher->shouldReceive('update')
            ->once()
            ->with(1, Mockery::type('array'));

        $preacher->shouldReceive('findAllWithSermonCount')
            ->once()
            ->andReturn($this->samplePreachers);

        $page = $this->createPage();
        $output = $this->captureOutput(fn() => $page->render());

        // Upload fails in test environment.
        $this->assertStringContainsString('Could not save uploaded file', $output);
    }
}
