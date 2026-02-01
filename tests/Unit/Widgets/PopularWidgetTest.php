<?php

/**
 * Tests for PopularWidget.
 *
 * @package SermonBrowser\Tests\Unit\Widgets
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Widgets;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Widgets\PopularWidget;
use Brain\Monkey\Functions;

/**
 * Test PopularWidget functionality.
 */
class PopularWidgetTest extends TestCase
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
        Functions\when('absint')->alias(function ($value) {
            return abs((int) $value);
        });
    }

    /**
     * Test update sanitizes title field.
     */
    public function testUpdateSanitizesTitle(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update(
            ['title' => 'Popular Sermons', 'limit' => '5'],
            []
        );

        $this->assertSame('Popular Sermons', $result['title']);
    }

    /**
     * Test update returns empty title when not provided.
     */
    public function testUpdateReturnsEmptyTitleWhenNotProvided(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update(['limit' => '5'], []);

        $this->assertSame('', $result['title']);
    }

    /**
     * Test update sanitizes limit field.
     */
    public function testUpdateSanitizesLimit(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update(['limit' => '10'], []);

        $this->assertSame(10, $result['limit']);
    }

    /**
     * Test update defaults limit to 5 when not provided.
     */
    public function testUpdateDefaultsLimitToFive(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update([], []);

        $this->assertSame(5, $result['limit']);
    }

    /**
     * Test update handles display_sermons checkbox when checked.
     */
    public function testUpdateHandlesDisplaySermonsCheckboxWhenChecked(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update(['display_sermons' => '1'], []);

        $this->assertTrue($result['display_sermons']);
    }

    /**
     * Test update handles display_sermons checkbox when unchecked.
     */
    public function testUpdateHandlesDisplaySermonsCheckboxWhenUnchecked(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update([], []);

        $this->assertFalse($result['display_sermons']);
    }

    /**
     * Test update handles display_series checkbox when checked.
     */
    public function testUpdateHandlesDisplaySeriesCheckboxWhenChecked(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update(['display_series' => '1'], []);

        $this->assertTrue($result['display_series']);
    }

    /**
     * Test update handles display_series checkbox when unchecked.
     */
    public function testUpdateHandlesDisplaySeriesCheckboxWhenUnchecked(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update([], []);

        $this->assertFalse($result['display_series']);
    }

    /**
     * Test update handles display_preachers checkbox when checked.
     */
    public function testUpdateHandlesDisplayPreachersCheckboxWhenChecked(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update(['display_preachers' => '1'], []);

        $this->assertTrue($result['display_preachers']);
    }

    /**
     * Test update handles display_preachers checkbox when unchecked.
     */
    public function testUpdateHandlesDisplayPreachersCheckboxWhenUnchecked(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update([], []);

        $this->assertFalse($result['display_preachers']);
    }

    /**
     * Test update with all options enabled.
     */
    public function testUpdateWithAllOptionsEnabled(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update(
            [
                'title' => 'My Popular',
                'limit' => '15',
                'display_sermons' => '1',
                'display_series' => '1',
                'display_preachers' => '1',
            ],
            []
        );

        $this->assertSame('My Popular', $result['title']);
        $this->assertSame(15, $result['limit']);
        $this->assertTrue($result['display_sermons']);
        $this->assertTrue($result['display_series']);
        $this->assertTrue($result['display_preachers']);
    }

    /**
     * Test update with all options disabled.
     */
    public function testUpdateWithAllOptionsDisabled(): void
    {
        $widget = new PopularWidget();
        $result = $widget->update([], []);

        $this->assertSame('', $result['title']);
        $this->assertSame(5, $result['limit']);
        $this->assertFalse($result['display_sermons']);
        $this->assertFalse($result['display_series']);
        $this->assertFalse($result['display_preachers']);
    }

    /**
     * Test backward compatibility alias exists.
     */
    public function testBackwardCompatibilityAliasExists(): void
    {
        // Create widget instance to trigger class loading.
        new PopularWidget();

        $this->assertTrue(class_exists('SB_Popular_Widget'));
        $this->assertTrue(is_a('SB_Popular_Widget', PopularWidget::class, true));
    }

    /**
     * Test widget has correct ID base.
     */
    public function testWidgetHasCorrectIdBase(): void
    {
        $widget = new PopularWidget();

        $this->assertSame('sb_popular', $widget->id_base);
    }

    /**
     * Test widget has correct name.
     */
    public function testWidgetHasCorrectName(): void
    {
        $widget = new PopularWidget();

        $this->assertSame('Popular Sermons', $widget->name);
    }

    /**
     * Test widget has correct class name option.
     */
    public function testWidgetHasCorrectClassNameOption(): void
    {
        $widget = new PopularWidget();

        $this->assertSame('sb-popular-widget', $widget->widget_options['classname']);
    }
}
