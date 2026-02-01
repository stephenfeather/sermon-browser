<?php

/**
 * Tests for SermonsWidget.
 *
 * @package SermonBrowser\Tests\Unit\Widgets
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Widgets;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Widgets\SermonsWidget;
use Brain\Monkey\Functions;

/**
 * Test SermonsWidget functionality.
 */
class SermonsWidgetTest extends TestCase
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
        $widget = new SermonsWidget();
        $result = $widget->update(
            ['title' => 'Recent Sermons', 'limit' => '5'],
            []
        );

        $this->assertSame('Recent Sermons', $result['title']);
    }

    /**
     * Test update returns empty title when not provided.
     */
    public function testUpdateReturnsEmptyTitleWhenNotProvided(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertSame('', $result['title']);
    }

    /**
     * Test update sanitizes limit field.
     */
    public function testUpdateSanitizesLimit(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(['limit' => '10'], []);

        $this->assertSame(10, $result['limit']);
    }

    /**
     * Test update defaults limit to 5 when not provided.
     */
    public function testUpdateDefaultsLimitToFive(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertSame(5, $result['limit']);
    }

    /**
     * Test update sanitizes preacher ID.
     */
    public function testUpdateSanitizesPreacherId(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(['preacher' => '42', 'limit' => '5'], []);

        $this->assertSame(42, $result['preacher']);
    }

    /**
     * Test update defaults preacher to 0 when not provided.
     */
    public function testUpdateDefaultsPreacherToZero(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertSame(0, $result['preacher']);
    }

    /**
     * Test update sanitizes service ID.
     */
    public function testUpdateSanitizesServiceId(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(['service' => '7', 'limit' => '5'], []);

        $this->assertSame(7, $result['service']);
    }

    /**
     * Test update defaults service to 0 when not provided.
     */
    public function testUpdateDefaultsServiceToZero(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertSame(0, $result['service']);
    }

    /**
     * Test update sanitizes series ID.
     */
    public function testUpdateSanitizesSeriesId(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(['series' => '15', 'limit' => '5'], []);

        $this->assertSame(15, $result['series']);
    }

    /**
     * Test update defaults series to 0 when not provided.
     */
    public function testUpdateDefaultsSeriesToZero(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertSame(0, $result['series']);
    }

    /**
     * Test update handles show_preacher checkbox when checked.
     */
    public function testUpdateHandlesShowPreacherCheckboxWhenChecked(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(['show_preacher' => '1'], []);

        $this->assertTrue($result['show_preacher']);
    }

    /**
     * Test update handles show_preacher checkbox when unchecked.
     */
    public function testUpdateHandlesShowPreacherCheckboxWhenUnchecked(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertFalse($result['show_preacher']);
    }

    /**
     * Test update handles show_book checkbox when checked.
     */
    public function testUpdateHandlesShowBookCheckboxWhenChecked(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(['show_book' => '1'], []);

        $this->assertTrue($result['show_book']);
    }

    /**
     * Test update handles show_book checkbox when unchecked.
     */
    public function testUpdateHandlesShowBookCheckboxWhenUnchecked(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertFalse($result['show_book']);
    }

    /**
     * Test update handles show_date checkbox when checked.
     */
    public function testUpdateHandlesShowDateCheckboxWhenChecked(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(['show_date' => '1'], []);

        $this->assertTrue($result['show_date']);
    }

    /**
     * Test update handles show_date checkbox when unchecked.
     */
    public function testUpdateHandlesShowDateCheckboxWhenUnchecked(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertFalse($result['show_date']);
    }

    /**
     * Test update with all options configured.
     */
    public function testUpdateWithAllOptionsConfigured(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update(
            [
                'title' => 'My Sermons',
                'limit' => '10',
                'preacher' => '5',
                'service' => '3',
                'series' => '8',
                'show_preacher' => '1',
                'show_book' => '1',
                'show_date' => '1',
            ],
            []
        );

        $this->assertSame('My Sermons', $result['title']);
        $this->assertSame(10, $result['limit']);
        $this->assertSame(5, $result['preacher']);
        $this->assertSame(3, $result['service']);
        $this->assertSame(8, $result['series']);
        $this->assertTrue($result['show_preacher']);
        $this->assertTrue($result['show_book']);
        $this->assertTrue($result['show_date']);
    }

    /**
     * Test update with minimal configuration.
     */
    public function testUpdateWithMinimalConfiguration(): void
    {
        $widget = new SermonsWidget();
        $result = $widget->update([], []);

        $this->assertSame('', $result['title']);
        $this->assertSame(5, $result['limit']);
        $this->assertSame(0, $result['preacher']);
        $this->assertSame(0, $result['service']);
        $this->assertSame(0, $result['series']);
        $this->assertFalse($result['show_preacher']);
        $this->assertFalse($result['show_book']);
        $this->assertFalse($result['show_date']);
    }

    /**
     * Test backward compatibility alias exists.
     */
    public function testBackwardCompatibilityAliasExists(): void
    {
        // Create widget instance to trigger class loading.
        new SermonsWidget();

        $this->assertTrue(class_exists('SB_Sermons_Widget'));
        $this->assertTrue(is_a('SB_Sermons_Widget', SermonsWidget::class, true));
    }

    /**
     * Test widget has correct ID base.
     */
    public function testWidgetHasCorrectIdBase(): void
    {
        $widget = new SermonsWidget();

        $this->assertSame('sb_sermons', $widget->id_base);
    }

    /**
     * Test widget has correct name.
     */
    public function testWidgetHasCorrectName(): void
    {
        $widget = new SermonsWidget();

        $this->assertSame('Sermons', $widget->name);
    }

    /**
     * Test widget has correct class name option.
     */
    public function testWidgetHasCorrectClassNameOption(): void
    {
        $widget = new SermonsWidget();

        $this->assertSame('sb-sermons-widget', $widget->widget_options['classname']);
    }
}
