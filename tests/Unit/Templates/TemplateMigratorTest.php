<?php
/**
 * Tests for TemplateMigrator.
 *
 * @package SermonBrowser\Tests\Unit\Templates
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Templates;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Templates\TemplateMigrator;
use SermonBrowser\Templates\MigrationResult;
use SermonBrowser\Templates\TagRenderer;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test TemplateMigrator functionality.
 *
 * Tests the template migration process for plugin upgrades.
 */
class TemplateMigratorTest extends TestCase
{
    /**
     * Mock TagRenderer.
     *
     * @var TagRenderer|\Mockery\MockInterface
     */
    private $mockRenderer;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRenderer = Mockery::mock(TagRenderer::class);
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    /**
     * Test TemplateMigrator can be instantiated.
     */
    public function testCanBeInstantiated(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->assertInstanceOf(TemplateMigrator::class, $migrator);
    }

    /**
     * Test TemplateMigrator can be instantiated with default renderer.
     */
    public function testCanBeInstantiatedWithDefaultRenderer(): void
    {
        $migrator = new TemplateMigrator();

        $this->assertInstanceOf(TemplateMigrator::class, $migrator);
    }

    // =========================================================================
    // Migrate Method Tests
    // =========================================================================

    /**
     * Test migrate returns MigrationResult.
     */
    public function testMigrateReturnsMigrationResult(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title', 'preacher_link']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('[sermon_title]');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('[preacher_link]');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertInstanceOf(MigrationResult::class, $result);
    }

    // =========================================================================
    // Template Backup Tests
    // =========================================================================

    /**
     * Test migrate backs up search template.
     */
    public function testMigrateBacksUpSearchTemplate(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);
        $searchTemplate = '<div>[sermon_title]</div>';

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn($searchTemplate);

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('');

        Functions\expect('update_option')
            ->with('sb_search_template_backup', $searchTemplate)
            ->once()
            ->andReturn(true);

        Functions\expect('update_option')
            ->with('sb_single_template_backup', '')
            ->once()
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $migrator->migrate();
    }

    /**
     * Test migrate backs up single template.
     */
    public function testMigrateBacksUpSingleTemplate(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);
        $singleTemplate = '<h1>[sermon_title]</h1>';

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn($singleTemplate);

        Functions\expect('update_option')
            ->with('sb_search_template_backup', '')
            ->once()
            ->andReturn(true);

        Functions\expect('update_option')
            ->with('sb_single_template_backup', $singleTemplate)
            ->once()
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $migrator->migrate();
    }

    // =========================================================================
    // Tag Validation Tests
    // =========================================================================

    /**
     * Test migrate validates tags and returns success for known tags.
     */
    public function testMigrateReturnsSuccessForKnownTags(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title', 'preacher_link', 'date']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('[sermon_title] [date]');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('[preacher_link]');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->getUnknownTags());
    }

    /**
     * Test migrate identifies unknown tags.
     */
    public function testMigrateIdentifiesUnknownTags(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->times(2)
            ->andReturnUsing(function ($option) {
                return match ($option) {
                    'sb_search_template' => '[sermon_title] [unknown_tag]',
                    'sb_single_template' => '[another_unknown]',
                    default => false,
                };
            });

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->hasWarnings());
        $unknownTags = $result->getUnknownTags();
        $this->assertContains('unknown_tag', $unknownTags);
        $this->assertContains('another_unknown', $unknownTags);
    }

    /**
     * Test migrate handles templates with no tags.
     */
    public function testMigrateHandlesTemplatesWithNoTags(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('<div>Static content only</div>');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test migrate finds unknown tags in both templates.
     */
    public function testMigrateFindsUnknownTagsInBothTemplates(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->times(2)
            ->andReturnUsing(function ($option) {
                return match ($option) {
                    'sb_search_template' => '[unknown_search_tag]',
                    'sb_single_template' => '[unknown_single_tag]',
                    default => false,
                };
            });

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $unknownTags = $result->getUnknownTags();
        $this->assertContains('unknown_search_tag', $unknownTags);
        $this->assertContains('unknown_single_tag', $unknownTags);
    }

    // =========================================================================
    // Output Deletion Tests
    // =========================================================================

    /**
     * Test migrate deletes search output option.
     */
    public function testMigrateDeletesSearchOutputOption(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('[sermon_title]');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->with('sb_search_output')
            ->once()
            ->andReturn(true);

        Functions\expect('delete_option')
            ->with('sb_single_output')
            ->once()
            ->andReturn(true);

        $migrator->migrate();
    }

    /**
     * Test migrate deletes single output option.
     */
    public function testMigrateDeletesSingleOutputOption(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('[sermon_title]');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->with('sb_search_output')
            ->once()
            ->andReturn(true);

        Functions\expect('delete_option')
            ->with('sb_single_output')
            ->once()
            ->andReturn(true);

        $migrator->migrate();
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * Test migrate handles empty templates gracefully.
     */
    public function testMigrateHandlesEmptyTemplatesGracefully(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->getUnknownTags());
    }

    /**
     * Test migrate handles null templates from get_option.
     */
    public function testMigrateHandlesNullTemplates(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn(false);

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn(false);

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test migrate handles loop tags correctly (they are known tags).
     */
    public function testMigrateHandlesLoopTagsCorrectly(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermons_loop', '/sermons_loop', 'sermon_title']);

        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('[sermons_loop][sermon_title][/sermons_loop]');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test migrate does not treat HTML or text as tags.
     */
    public function testMigrateDoesNotTreatHtmlAsTags(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        // Template with HTML that looks tag-like but isn't
        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('<div class="sermon">[sermon_title]</div>');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test migrate returns unique unknown tags.
     */
    public function testMigrateReturnsUniqueUnknownTags(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn(['sermon_title']);

        // Template with the same unknown tag multiple times
        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn('[custom_tag] [custom_tag] [custom_tag]');

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn('[custom_tag]');

        Functions\expect('update_option')
            ->times(2)
            ->andReturn(true);

        Functions\expect('delete_option')
            ->times(2)
            ->andReturn(true);

        $result = $migrator->migrate();

        $unknownTags = $result->getUnknownTags();
        $this->assertCount(1, $unknownTags);
        $this->assertContains('custom_tag', $unknownTags);
    }

    // =========================================================================
    // Full Migration Flow Test
    // =========================================================================

    /**
     * Test complete migration flow.
     */
    public function testCompleteMigrationFlow(): void
    {
        $migrator = new TemplateMigrator($this->mockRenderer);
        $searchTemplate = '<ul>[sermons_loop]<li>[sermon_title] by [preacher_link]</li>[/sermons_loop]</ul>';
        $singleTemplate = '<h1>[sermon_title]</h1><p>[sermon_description]</p>';

        $this->mockRenderer
            ->shouldReceive('getAvailableTags')
            ->andReturn([
                'sermons_loop',
                '/sermons_loop',
                'sermon_title',
                'preacher_link',
                'sermon_description',
            ]);

        // Get templates
        Functions\expect('get_option')
            ->with('sb_search_template')
            ->andReturn($searchTemplate);

        Functions\expect('get_option')
            ->with('sb_single_template')
            ->andReturn($singleTemplate);

        // Backup templates
        Functions\expect('update_option')
            ->with('sb_search_template_backup', $searchTemplate)
            ->once()
            ->andReturn(true);

        Functions\expect('update_option')
            ->with('sb_single_template_backup', $singleTemplate)
            ->once()
            ->andReturn(true);

        // Delete output options
        Functions\expect('delete_option')
            ->with('sb_search_output')
            ->once()
            ->andReturn(true);

        Functions\expect('delete_option')
            ->with('sb_single_output')
            ->once()
            ->andReturn(true);

        $result = $migrator->migrate();

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->hasWarnings());
        $this->assertEmpty($result->getUnknownTags());
        $this->assertStringContainsString('success', strtolower($result->getMessage()));
    }
}
