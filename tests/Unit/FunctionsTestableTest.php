<?php
/**
 * Tests for pure, testable functions.
 *
 * @package SermonBrowser\Tests\Unit
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit;

use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

// Load the functions file for testing.
require_once dirname(__DIR__, 2) . '/sb-includes/functions-testable.php';

/**
 * Test class for functions-testable.php.
 */
class FunctionsTestableTest extends TestCase
{
    /**
     * Test sb_generate_temp_suffix generates correct length.
     */
    public function testGenerateTempSuffixLength(): void
    {
        $result = sb_generate_temp_suffix(5);

        $this->assertEquals(5, strlen($result));
    }

    /**
     * Test sb_generate_temp_suffix generates only lowercase letters.
     */
    public function testGenerateTempSuffixOnlyLowercase(): void
    {
        // Generate a longer string to have more confidence.
        $result = sb_generate_temp_suffix(100);

        $this->assertMatchesRegularExpression('/^[a-z]+$/', $result);
    }

    /**
     * Test sb_generate_temp_suffix with default length.
     */
    public function testGenerateTempSuffixDefaultLength(): void
    {
        $result = sb_generate_temp_suffix();

        $this->assertEquals(2, strlen($result));
    }

    /**
     * Test sb_join_passages joins correctly.
     */
    public function testJoinPassagesBasic(): void
    {
        $passages = ['Genesis 1:1', 'John 3:16', 'Romans 8:28'];

        $result = sb_join_passages($passages);

        $this->assertEquals('Genesis 1:1, John 3:16, Romans 8:28', $result);
    }

    /**
     * Test sb_join_passages with custom separator.
     */
    public function testJoinPassagesCustomSeparator(): void
    {
        $passages = ['Genesis 1:1', 'John 3:16'];

        $result = sb_join_passages($passages, ' | ');

        $this->assertEquals('Genesis 1:1 | John 3:16', $result);
    }

    /**
     * Test sb_join_passages with empty array.
     */
    public function testJoinPassagesEmptyArray(): void
    {
        $result = sb_join_passages([]);

        $this->assertEquals('', $result);
    }

    /**
     * Test sb_join_passages with single element.
     */
    public function testJoinPassagesSingleElement(): void
    {
        $result = sb_join_passages(['Genesis 1:1']);

        $this->assertEquals('Genesis 1:1', $result);
    }

    /**
     * Test sb_get_locale_string returns locale with UTF-8.
     */
    public function testGetLocaleStringReturnsUtf8Suffix(): void
    {
        // Override the default stub for get_locale.
        Functions\expect('get_locale')
            ->once()
            ->andReturn('en_US');

        $result = sb_get_locale_string();

        $this->assertEquals('en_US.UTF-8', $result);
    }

    /**
     * Test sb_get_locale_string with empty locale.
     */
    public function testGetLocaleStringEmptyLocale(): void
    {
        Functions\expect('get_locale')
            ->once()
            ->andReturn('');

        $result = sb_get_locale_string();

        $this->assertEquals('', $result);
    }

    /**
     * Test sb_is_super_admin returns true when user is super admin.
     */
    public function testIsSuperAdminReturnsTrue(): void
    {
        Functions\expect('is_super_admin')
            ->once()
            ->andReturn(true);

        $result = sb_is_super_admin();

        $this->assertTrue($result);
    }

    /**
     * Test sb_is_super_admin returns false when user is not super admin.
     */
    public function testIsSuperAdminReturnsFalse(): void
    {
        Functions\expect('is_super_admin')
            ->once()
            ->andReturn(false);

        $result = sb_is_super_admin();

        $this->assertFalse($result);
    }
}
