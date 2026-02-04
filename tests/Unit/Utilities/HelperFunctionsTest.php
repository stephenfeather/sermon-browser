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

    /**
     * Test returnKbytes with megabytes.
     */
    public function testReturnKbytesWithMegabytes(): void
    {
        $result = HelperFunctions::returnKbytes('15M');

        $this->assertSame(15360, $result); // 15 * 1024
    }

    /**
     * Test returnKbytes with lowercase megabytes.
     */
    public function testReturnKbytesWithLowercaseMegabytes(): void
    {
        $result = HelperFunctions::returnKbytes('15m');

        $this->assertSame(15360, $result);
    }

    /**
     * Test returnKbytes with gigabytes.
     */
    public function testReturnKbytesWithGigabytes(): void
    {
        $result = HelperFunctions::returnKbytes('1G');

        $this->assertSame(1048576, $result); // 1 * 1024 * 1024
    }

    /**
     * Test returnKbytes with lowercase gigabytes.
     */
    public function testReturnKbytesWithLowercaseGigabytes(): void
    {
        $result = HelperFunctions::returnKbytes('2g');

        $this->assertSame(2097152, $result); // 2 * 1024 * 1024
    }

    /**
     * Test returnKbytes with plain number (no suffix).
     */
    public function testReturnKbytesWithNoSuffix(): void
    {
        $result = HelperFunctions::returnKbytes('1024');

        $this->assertSame(1024, $result);
    }

    /**
     * Test returnKbytes with empty string.
     */
    public function testReturnKbytesWithEmptyString(): void
    {
        $result = HelperFunctions::returnKbytes('');

        $this->assertSame(0, $result);
    }

    /**
     * Test returnKbytes with whitespace.
     */
    public function testReturnKbytesTrimsWhitespace(): void
    {
        $result = HelperFunctions::returnKbytes('  15M  ');

        $this->assertSame(15360, $result);
    }

    /**
     * Test sanitisePath converts backslashes to forward slashes.
     */
    public function testSanitisePathConvertsBackslashes(): void
    {
        $result = HelperFunctions::sanitisePath('C:\\Users\\test\\file.txt');

        $this->assertSame('C:/Users/test/file.txt', $result);
    }

    /**
     * Test sanitisePath removes duplicate slashes.
     */
    public function testSanitisePathRemovesDuplicateSlashes(): void
    {
        $result = HelperFunctions::sanitisePath('/var//www///html/file.txt');

        $this->assertSame('/var/www/html/file.txt', $result);
    }

    /**
     * Test sanitisePath handles mixed slashes.
     */
    public function testSanitisePathHandlesMixedSlashes(): void
    {
        $result = HelperFunctions::sanitisePath('C:\\Users//test\\\\file.txt');

        $this->assertSame('C:/Users/test/file.txt', $result);
    }

    /**
     * Test sanitisePath with already clean path.
     */
    public function testSanitisePathWithCleanPath(): void
    {
        $result = HelperFunctions::sanitisePath('/var/www/html/file.txt');

        $this->assertSame('/var/www/html/file.txt', $result);
    }

    /**
     * Test sanitisePath with empty string.
     */
    public function testSanitisePathWithEmptyString(): void
    {
        $result = HelperFunctions::sanitisePath('');

        $this->assertSame('', $result);
    }

    /**
     * Test safeUnserialize with valid serialized array.
     */
    public function testSafeUnserializeWithValidArray(): void
    {
        $data = serialize([1, 'Genesis', 3]);

        $result = HelperFunctions::safeUnserialize($data);

        $this->assertSame([1, 'Genesis', 3], $result);
    }

    /**
     * Test safeUnserialize with empty string returns null.
     */
    public function testSafeUnserializeWithEmptyString(): void
    {
        $result = HelperFunctions::safeUnserialize('');

        $this->assertNull($result);
    }

    /**
     * Test safeUnserialize with invalid format returns null.
     */
    public function testSafeUnserializeWithInvalidFormat(): void
    {
        $result = HelperFunctions::safeUnserialize('not a serialized string');

        $this->assertNull($result);
    }

    /**
     * Test safeUnserialize rejects serialized objects.
     */
    public function testSafeUnserializeRejectsObjects(): void
    {
        // Serialized stdClass object starts with 'O:' not 'a:'
        $objectData = 'O:8:"stdClass":1:{s:4:"name";s:4:"test";}';

        $result = HelperFunctions::safeUnserialize($objectData);

        $this->assertNull($result);
    }

    /**
     * Test safeUnserialize with nested array.
     */
    public function testSafeUnserializeWithNestedArray(): void
    {
        $data = serialize(['book' => 'Genesis', 'chapters' => [1, 2, 3]]);

        $result = HelperFunctions::safeUnserialize($data);

        $this->assertSame(['book' => 'Genesis', 'chapters' => [1, 2, 3]], $result);
    }

    /**
     * Test safeUnserialize with malformed serialized data returns null.
     */
    public function testSafeUnserializeWithMalformedData(): void
    {
        // Looks like it starts with array format but is corrupted
        $malformed = 'a:2:{corrupted data';

        $result = HelperFunctions::safeUnserialize($malformed);

        $this->assertNull($result);
    }
}
