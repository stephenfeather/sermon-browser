<?php

/**
 * Tests for HelpPage class.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Pages
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Pages;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Pages\HelpPage;
use Brain\Monkey\Functions;

/**
 * Test HelpPage functionality.
 */
class HelpPageTest extends TestCase
{
    /**
     * HelpPage instance under test.
     *
     * @var HelpPage
     */
    private HelpPage $page;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Define plugin URL constant if not already defined.
        if (!defined('SB_PLUGIN_URL')) {
            define('SB_PLUGIN_URL', 'http://example.com/wp-content/plugins/sermon-browser');
        }

        // Stub WordPress functions.
        Functions\stubs([
            'esc_attr_e' => static function (string $text, string $domain = 'default'): void {
                print(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
            },
            'admin_url'  => static function (string $path = ''): string {
                return 'http://example.com/wp-admin/' . ltrim($path, '/');
            },
        ]);

        $this->page = new HelpPage();
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
     * Test render method calls sb_do_alerts and outputs help content.
     */
    public function testRenderCallsDoAlertsAndOutputsHelpContent(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Thank you', $output);
        $this->assertStringContainsString('Help page', $output);
        $this->assertStringContainsString('sermonbrowser.com', $output);
    }

    /**
     * Test render outputs the logo image.
     */
    public function testRenderOutputsLogo(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('logo-small.png', $output);
        $this->assertStringContainsString('sermon-browser', $output);
    }

    /**
     * Test render outputs the thank you section with donors.
     */
    public function testRenderOutputsThankYouSection(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        // Check for donor churches.
        $this->assertStringContainsString('Cambray Baptist Church', $output);
        $this->assertStringContainsString('Bethel Evangelical Church', $output);
        $this->assertStringContainsString('donated', $output);
    }

    /**
     * Test render outputs the help documentation section.
     */
    public function testRenderOutputsHelpSection(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        // Check for help content sections.
        $this->assertStringContainsString('Screencasts', $output);
        $this->assertStringContainsString('Template tags', $output);
        $this->assertStringContainsString('Shortcode', $output);
        $this->assertStringContainsString('Frequently asked questions', $output);
        $this->assertStringContainsString('Further help', $output);
    }

    /**
     * Test render outputs links to tutorials.
     */
    public function testRenderOutputsTutorialLinks(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Installation and Overview', $output);
        $this->assertStringContainsString('Basic Options', $output);
        $this->assertStringContainsString('Preachers, Series and Services', $output);
        $this->assertStringContainsString('Entering a new sermon', $output);
    }

    /**
     * Test renderJapan method calls sb_do_alerts and outputs Japan content.
     */
    public function testRenderJapanCallsDoAlertsAndOutputsJapanContent(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->renderJapan());

        $this->assertStringContainsString('Japan', $output);
        $this->assertStringContainsString('Nathanael and Anna Ayling', $output);
    }

    /**
     * Test renderJapan outputs the logo image.
     */
    public function testRenderJapanOutputsLogo(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->renderJapan());

        $this->assertStringContainsString('logo-small.png', $output);
        $this->assertStringContainsString('sermon-browser', $output);
    }

    /**
     * Test renderJapan outputs video iframe.
     */
    public function testRenderJapanOutputsVideoIframe(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->renderJapan());

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('vimeo.com', $output);
    }

    /**
     * Test renderJapan outputs ministry support information.
     */
    public function testRenderJapanOutputsMinistrySupportInfo(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->renderJapan());

        $this->assertStringContainsString('OMF International', $output);
        $this->assertStringContainsString('Sappororo', $output);
        $this->assertStringContainsString('donated', $output);
    }

    /**
     * Test renderJapan outputs donation links.
     */
    public function testRenderJapanOutputsDonationLinks(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->renderJapan());

        $this->assertStringContainsString('blog', $output);
        $this->assertStringContainsString('prayer news', $output);
        $this->assertStringContainsString('PayPal', $output);
    }

    /**
     * Test renderJapan outputs ministry heading.
     */
    public function testRenderJapanOutputsMinistryHeading(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->renderJapan());

        $this->assertStringContainsString('Help support Christian ministry in Japan', $output);
    }

    /**
     * Test render outputs contributors section.
     */
    public function testRenderOutputsContributorsSection(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('Additional help was also received from', $output);
        $this->assertStringContainsString('Tien Do Xuan', $output);
        $this->assertStringContainsString('Spanish translation', $output);
        $this->assertStringContainsString('German translation', $output);
    }

    /**
     * Test render outputs FAQ link.
     */
    public function testRenderOutputsFaqLink(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('sermonbrowser.com/faq', $output);
        $this->assertStringContainsString('comprehensive FAQ', $output);
    }

    /**
     * Test render outputs forum link.
     */
    public function testRenderOutputsForumLink(): void
    {
        Functions\expect('sb_do_alerts')
            ->once();

        $output = $this->captureOutput(fn() => $this->page->render());

        $this->assertStringContainsString('sermonbrowser.com/forum', $output);
        $this->assertStringContainsString('SermonBrowser forum', $output);
    }
}
