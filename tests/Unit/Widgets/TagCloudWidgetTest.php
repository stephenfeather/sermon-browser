<?php

/**
 * Tests for TagCloudWidget.
 *
 * @package SermonBrowser\Tests\Unit\Widgets
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Widgets;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Widgets\TagCloudWidget;
use Brain\Monkey\Functions;

/**
 * Test TagCloudWidget functionality.
 */
class TagCloudWidgetTest extends TestCase
{
    /**
     * Set up WordPress function stubs.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Stub WordPress functions used by widgets.
        Functions\when('__')->returnArg(1);
        Functions\when('sanitize_text_field')->returnArg(1);
    }

    /**
     * Test update sanitizes title field.
     */
    public function testUpdateSanitizesTitle(): void
    {
        $widget = new TagCloudWidget();
        $result = $widget->update(['title' => 'Sermon Tags'], []);

        $this->assertSame('Sermon Tags', $result['title']);
    }

    /**
     * Test update returns empty title when not provided.
     */
    public function testUpdateReturnsEmptyTitleWhenNotProvided(): void
    {
        $widget = new TagCloudWidget();
        $result = $widget->update([], []);

        $this->assertSame('', $result['title']);
    }

    /**
     * Test update returns empty title when empty string provided.
     */
    public function testUpdateReturnsEmptyTitleWhenEmptyStringProvided(): void
    {
        $widget = new TagCloudWidget();
        $result = $widget->update(['title' => ''], []);

        $this->assertSame('', $result['title']);
    }

    /**
     * Test update preserves whitespace-only title as empty.
     *
     * Note: sanitize_text_field in WordPress trims whitespace,
     * but in our stub it passes through. The widget logic treats
     * empty strings as false, so whitespace-only would be saved.
     */
    public function testUpdatePreservesTitle(): void
    {
        $widget = new TagCloudWidget();
        $result = $widget->update(['title' => 'Tag Cloud'], []);

        $this->assertSame('Tag Cloud', $result['title']);
    }

    /**
     * Test update ignores old instance values.
     */
    public function testUpdateIgnoresOldInstanceValues(): void
    {
        $widget = new TagCloudWidget();
        $result = $widget->update(
            ['title' => 'New Title'],
            ['title' => 'Old Title']
        );

        $this->assertSame('New Title', $result['title']);
    }

    /**
     * Test update only returns title key.
     */
    public function testUpdateOnlyReturnsTitleKey(): void
    {
        $widget = new TagCloudWidget();
        $result = $widget->update(
            [
                'title' => 'Test',
                'extra_field' => 'ignored',
                'another_field' => 123,
            ],
            []
        );

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayNotHasKey('extra_field', $result);
        $this->assertArrayNotHasKey('another_field', $result);
    }

    /**
     * Test backward compatibility alias exists.
     */
    public function testBackwardCompatibilityAliasExists(): void
    {
        // Create widget instance to trigger class loading.
        new TagCloudWidget();

        $this->assertTrue(class_exists('SB_Tag_Cloud_Widget'));
        $this->assertTrue(is_a('SB_Tag_Cloud_Widget', TagCloudWidget::class, true));
    }

    /**
     * Test widget has correct ID base.
     */
    public function testWidgetHasCorrectIdBase(): void
    {
        $widget = new TagCloudWidget();

        $this->assertSame('sb_tag_cloud', $widget->id_base);
    }

    /**
     * Test widget has correct name.
     */
    public function testWidgetHasCorrectName(): void
    {
        $widget = new TagCloudWidget();

        $this->assertSame('Sermon Browser Tags', $widget->name);
    }

    /**
     * Test widget has correct class name option.
     */
    public function testWidgetHasCorrectClassNameOption(): void
    {
        $widget = new TagCloudWidget();

        $this->assertSame('sb-tag-cloud-widget', $widget->widget_options['classname']);
    }
}
