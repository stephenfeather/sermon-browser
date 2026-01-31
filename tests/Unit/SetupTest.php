<?php

/**
 * Setup verification test.
 *
 * Verifies that the test infrastructure is working correctly.
 *
 * @package SermonBrowser\Tests\Unit
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit;

use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test class to verify the testing infrastructure works.
 */
class SetupTest extends TestCase
{
    /**
     * Test that PHPUnit is working.
     */
    public function testPhpunitWorks(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test that Brain Monkey function stubs work.
     */
    public function testBrainMonkeyStubsWork(): void
    {
        // The esc_html stub should escape HTML.
        $result = esc_html('<script>alert("xss")</script>');

        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /**
     * Test that Brain Monkey function expectations work.
     */
    public function testBrainMonkeyExpectationsWork(): void
    {
        // Set up an expectation for a WordPress function.
        Functions\expect('get_option')
            ->once()
            ->with('sermon_browser_option', 'default')
            ->andReturn('test_value');

        // Call the function.
        $result = get_option('sermon_browser_option', 'default');

        // Verify the result.
        $this->assertEquals('test_value', $result);
    }

    /**
     * Test that translation stubs return original text.
     */
    public function testTranslationStubsWork(): void
    {
        $text = __('Hello World', 'sermon-browser');

        $this->assertEquals('Hello World', $text);
    }

    /**
     * Test that plugin constants are defined.
     */
    public function testPluginConstantsDefined(): void
    {
        $this->assertTrue(defined('SB_CURRENT_VERSION'));
        $this->assertTrue(defined('SB_PLUGIN_DIR'));
        $this->assertTrue(defined('SB_INCLUDES_DIR'));
        $this->assertTrue(defined('ABSPATH'));
    }

    /**
     * Test Mockery works for creating mocks.
     */
    public function testMockeryWorks(): void
    {
        $mock = \Mockery::mock('stdClass');
        $mock->shouldReceive('getValue')->once()->andReturn(42);

        $this->assertEquals(42, $mock->getValue());
    }
}
