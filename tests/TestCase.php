<?php
/**
 * Base test case for Sermon Browser tests.
 *
 * Provides Brain Monkey setup/teardown for WordPress function mocking.
 *
 * @package SermonBrowser\Tests
 */

declare(strict_types=1);

namespace SermonBrowser\Tests;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case with Brain Monkey integration.
 *
 * Extend this class for tests that need to mock WordPress functions.
 */
abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Set up Brain Monkey before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Define common WordPress functions that are used frequently.
        $this->defineCommonWordPressFunctions();
    }

    /**
     * Tear down Brain Monkey after each test.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Define common WordPress functions used throughout the plugin.
     *
     * These are stubs that return sensible defaults.
     * Override in specific tests when you need different behavior.
     */
    protected function defineCommonWordPressFunctions(): void
    {
        // Escaping functions - pass through by default.
        Monkey\Functions\stubs([
            'esc_html'       => static fn($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'),
            'esc_attr'       => static fn($text) => htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'),
            'esc_url'        => static fn($url) => filter_var($url, FILTER_SANITIZE_URL),
            'esc_sql'        => static fn($data) => addslashes((string) $data),
            'wp_kses_post'   => static fn($text) => $text,
            'sanitize_text_field' => static fn($text) => trim(strip_tags((string) $text)),
        ]);

        // Translation functions - return the original text.
        Monkey\Functions\stubs([
            '__'       => static fn($text, $domain = 'default') => $text,
            '_e'       => static fn($text, $domain = 'default') => print($text),
            'esc_html__' => static fn($text, $domain = 'default') => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            'esc_attr__' => static fn($text, $domain = 'default') => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
        ]);

        // Note: get_option, update_option, delete_option, and get_locale are NOT stubbed here
        // because individual tests may need to set specific expectations for them.
        // Use Brain\Monkey\Functions\expect() in your tests for these functions.
    }
}
