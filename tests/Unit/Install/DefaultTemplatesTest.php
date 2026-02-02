<?php

/**
 * Tests for Install\DefaultTemplates class.
 *
 * @package SermonBrowser\Tests\Unit\Install
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Install;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Install\DefaultTemplates;

/**
 * Test class for DefaultTemplates.
 */
class DefaultTemplatesTest extends TestCase
{
    /**
     * Test multiTemplate returns a non-empty string.
     */
    public function testMultiTemplateReturnsString(): void
    {
        $template = DefaultTemplates::multiTemplate();

        $this->assertIsString($template);
        $this->assertNotEmpty($template);
    }

    /**
     * Test multiTemplate contains expected template tags.
     */
    public function testMultiTemplateContainsExpectedTags(): void
    {
        $template = DefaultTemplates::multiTemplate();

        $this->assertStringContainsString('[filters_form]', $template);
        $this->assertStringContainsString('[sermons_loop]', $template);
        $this->assertStringContainsString('[/sermons_loop]', $template);
        $this->assertStringContainsString('[sermon_title]', $template);
        $this->assertStringContainsString('[sermons_count]', $template);
        $this->assertStringContainsString('[podcast]', $template);
        $this->assertStringContainsString('[next_page]', $template);
        $this->assertStringContainsString('[previous_page]', $template);
        $this->assertStringContainsString('[preacher_link]', $template);
        $this->assertStringContainsString('[date]', $template);
    }

    /**
     * Test multiTemplate contains sermon browser wrapper div.
     */
    public function testMultiTemplateHasWrapperDiv(): void
    {
        $template = DefaultTemplates::multiTemplate();

        $this->assertStringContainsString('<div class="sermon-browser">', $template);
        $this->assertStringContainsString('</div>', $template);
    }

    /**
     * Test singleTemplate returns a non-empty string.
     */
    public function testSingleTemplateReturnsString(): void
    {
        $template = DefaultTemplates::singleTemplate();

        $this->assertIsString($template);
        $this->assertNotEmpty($template);
    }

    /**
     * Test singleTemplate contains expected template tags.
     */
    public function testSingleTemplateContainsExpectedTags(): void
    {
        $template = DefaultTemplates::singleTemplate();

        $this->assertStringContainsString('[sermon_title]', $template);
        $this->assertStringContainsString('[passages_loop]', $template);
        $this->assertStringContainsString('[/passages_loop]', $template);
        $this->assertStringContainsString('[preacher_image]', $template);
        $this->assertStringContainsString('[preacher_link]', $template);
        $this->assertStringContainsString('[series_link]', $template);
        $this->assertStringContainsString('[service_link]', $template);
        $this->assertStringContainsString('[sermon_description]', $template);
        $this->assertStringContainsString('[files_loop]', $template);
        $this->assertStringContainsString('[/files_loop]', $template);
        $this->assertStringContainsString('[embed_loop]', $template);
        $this->assertStringContainsString('[/embed_loop]', $template);
    }

    /**
     * Test singleTemplate contains navigation elements.
     */
    public function testSingleTemplateContainsNavigation(): void
    {
        $template = DefaultTemplates::singleTemplate();

        $this->assertStringContainsString('[prev_sermon]', $template);
        $this->assertStringContainsString('[next_sermon]', $template);
        $this->assertStringContainsString('[sameday_sermon]', $template);
        $this->assertStringContainsString('table class="nearby-sermons"', $template);
    }

    /**
     * Test singleTemplate has sermon browser results wrapper.
     */
    public function testSingleTemplateHasResultsWrapper(): void
    {
        $template = DefaultTemplates::singleTemplate();

        $this->assertStringContainsString('<div class="sermon-browser-results">', $template);
    }

    /**
     * Test excerptTemplate returns a non-empty string.
     */
    public function testExcerptTemplateReturnsString(): void
    {
        $template = DefaultTemplates::excerptTemplate();

        $this->assertIsString($template);
        $this->assertNotEmpty($template);
    }

    /**
     * Test excerptTemplate contains expected template tags.
     */
    public function testExcerptTemplateContainsExpectedTags(): void
    {
        $template = DefaultTemplates::excerptTemplate();

        $this->assertStringContainsString('[sermons_loop]', $template);
        $this->assertStringContainsString('[/sermons_loop]', $template);
        $this->assertStringContainsString('[sermon_title]', $template);
        $this->assertStringContainsString('[first_passage]', $template);
        $this->assertStringContainsString('[series_link]', $template);
        $this->assertStringContainsString('[files_loop]', $template);
        $this->assertStringContainsString('[embed_loop]', $template);
        $this->assertStringContainsString('[preacher_link]', $template);
        $this->assertStringContainsString('[date]', $template);
        $this->assertStringContainsString('[service_link]', $template);
    }

    /**
     * Test excerptTemplate has sermon browser wrapper.
     */
    public function testExcerptTemplateHasWrapper(): void
    {
        $template = DefaultTemplates::excerptTemplate();

        $this->assertStringContainsString('<div class="sermon-browser">', $template);
    }

    /**
     * Test excerptTemplate is simpler than multiTemplate (no pagination).
     */
    public function testExcerptTemplateHasNoPagination(): void
    {
        $template = DefaultTemplates::excerptTemplate();

        $this->assertStringNotContainsString('[next_page]', $template);
        $this->assertStringNotContainsString('[previous_page]', $template);
        $this->assertStringNotContainsString('[filters_form]', $template);
    }

    /**
     * Test defaultCss returns a non-empty string.
     */
    public function testDefaultCssReturnsString(): void
    {
        $css = DefaultTemplates::defaultCss();

        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    /**
     * Test defaultCss replaces placeholder with plugin URL.
     */
    public function testDefaultCssReplacesPlaceholder(): void
    {
        $css = DefaultTemplates::defaultCss();

        $this->assertStringNotContainsString('**SB_PATH**', $css);
        $this->assertStringContainsString(SB_PLUGIN_URL, $css);
    }

    /**
     * Test defaultCss contains base styles.
     */
    public function testDefaultCssContainsBaseStyles(): void
    {
        $css = DefaultTemplates::defaultCss();

        $this->assertStringContainsString('.sermon-browser', $css);
        $this->assertStringContainsString('.sermon-title', $css);
        $this->assertStringContainsString('.sermon-passage', $css);
        $this->assertStringContainsString('.floatright', $css);
        $this->assertStringContainsString('.floatleft', $css);
    }

    /**
     * Test defaultCss contains podcast styles.
     */
    public function testDefaultCssContainsPodcastStyles(): void
    {
        $css = DefaultTemplates::defaultCss();

        $this->assertStringContainsString('table.podcast', $css);
        $this->assertStringContainsString('.podcastall', $css);
        $this->assertStringContainsString('.podcastcustom', $css);
        $this->assertStringContainsString('.podcast-icon', $css);
    }

    /**
     * Test defaultCss contains results page styles.
     */
    public function testDefaultCssContainsResultsStyles(): void
    {
        $css = DefaultTemplates::defaultCss();

        $this->assertStringContainsString('.sermon-browser-results', $css);
        $this->assertStringContainsString('.nearby-sermons', $css);
        $this->assertStringContainsString('.chapter-num', $css);
        $this->assertStringContainsString('.verse-num', $css);
    }

    /**
     * Test defaultCss contains widget styles.
     */
    public function testDefaultCssContainsWidgetStyles(): void
    {
        $css = DefaultTemplates::defaultCss();

        $this->assertStringContainsString('ul.sermon-widget', $css);
        $this->assertStringContainsString('.sb_edit_link', $css);
        $this->assertStringContainsString('.sb-clear', $css);
    }

    /**
     * Test all public methods are static.
     */
    public function testAllPublicMethodsAreStatic(): void
    {
        $methods = ['multiTemplate', 'singleTemplate', 'excerptTemplate', 'defaultCss'];

        foreach ($methods as $method) {
            $reflection = new \ReflectionMethod(DefaultTemplates::class, $method);
            $this->assertTrue(
                $reflection->isStatic(),
                "Method {$method} should be static"
            );
            $this->assertTrue(
                $reflection->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Test private helper methods exist.
     */
    public function testPrivateHelperMethodsExist(): void
    {
        $methods = ['getBaseStyles', 'getPodcastStyles', 'getResultsStyles', 'getWidgetStyles'];

        foreach ($methods as $method) {
            $reflection = new \ReflectionMethod(DefaultTemplates::class, $method);
            $this->assertTrue(
                $reflection->isPrivate(),
                "Method {$method} should be private"
            );
            $this->assertTrue(
                $reflection->isStatic(),
                "Method {$method} should be static"
            );
        }
    }
}
