<?php

/**
 * Tests for Utilities\HelperFunctions class.
 *
 * @package SermonBrowser\Tests\Unit\Utilities
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Utilities;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Utilities\HelperFunctions;
use Brain\Monkey\Functions;

/**
 * Test class for HelperFunctions.
 */
class HelperFunctionsTest extends TestCase
{
    /**
     * Test generateTempSuffix generates correct length.
     */
    public function testGenerateTempSuffixLength(): void
    {
        $result = HelperFunctions::generateTempSuffix(5);

        $this->assertEquals(5, strlen($result));
    }

    /**
     * Test generateTempSuffix generates only lowercase letters.
     */
    public function testGenerateTempSuffixOnlyLowercase(): void
    {
        // Generate a longer string to have more confidence
        $result = HelperFunctions::generateTempSuffix(100);

        $this->assertMatchesRegularExpression('/^[a-z]+$/', $result);
    }

    /**
     * Test generateTempSuffix with default length.
     */
    public function testGenerateTempSuffixDefaultLength(): void
    {
        $result = HelperFunctions::generateTempSuffix();

        $this->assertEquals(2, strlen($result));
    }

    /**
     * Test joinPassages joins correctly.
     */
    public function testJoinPassagesBasic(): void
    {
        $passages = ['Genesis 1:1', 'John 3:16', 'Romans 8:28'];

        $result = HelperFunctions::joinPassages($passages);

        $this->assertEquals('Genesis 1:1, John 3:16, Romans 8:28', $result);
    }

    /**
     * Test joinPassages with custom separator.
     */
    public function testJoinPassagesCustomSeparator(): void
    {
        $passages = ['Genesis 1:1', 'John 3:16'];

        $result = HelperFunctions::joinPassages($passages, ' | ');

        $this->assertEquals('Genesis 1:1 | John 3:16', $result);
    }

    /**
     * Test joinPassages with empty array.
     */
    public function testJoinPassagesEmptyArray(): void
    {
        $result = HelperFunctions::joinPassages([]);

        $this->assertEquals('', $result);
    }

    /**
     * Test getLocaleString returns locale with UTF-8.
     */
    public function testGetLocaleStringReturnsUtf8Suffix(): void
    {
        Functions\expect('get_locale')
            ->once()
            ->andReturn('en_US');

        $result = HelperFunctions::getLocaleString();

        $this->assertEquals('en_US.UTF-8', $result);
    }

    /**
     * Test getLocaleString with empty locale.
     */
    public function testGetLocaleStringEmptyLocale(): void
    {
        Functions\expect('get_locale')
            ->once()
            ->andReturn('');

        $result = HelperFunctions::getLocaleString();

        $this->assertEquals('', $result);
    }

    /**
     * Test isSuperAdmin returns true when user is super admin.
     */
    public function testIsSuperAdminReturnsTrue(): void
    {
        Functions\expect('is_super_admin')
            ->once()
            ->andReturn(true);

        $result = HelperFunctions::isSuperAdmin();

        $this->assertTrue($result);
    }

    /**
     * Test isSuperAdmin returns false when user is not super admin.
     */
    public function testIsSuperAdminReturnsFalse(): void
    {
        Functions\expect('is_super_admin')
            ->once()
            ->andReturn(false);

        $result = HelperFunctions::isSuperAdmin();

        $this->assertFalse($result);
    }
}
