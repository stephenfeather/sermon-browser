<?php
/**
 * Tests for MigrationResult value class.
 *
 * @package SermonBrowser\Tests\Unit\Templates
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Templates;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Templates\MigrationResult;

/**
 * Test MigrationResult functionality.
 *
 * Tests the value class that holds migration results and messages.
 */
class MigrationResultTest extends TestCase
{
    // =========================================================================
    // Constructor Tests
    // =========================================================================

    /**
     * Test MigrationResult can be instantiated with no arguments.
     */
    public function testCanBeInstantiatedWithNoArguments(): void
    {
        $result = new MigrationResult();

        $this->assertInstanceOf(MigrationResult::class, $result);
    }

    /**
     * Test MigrationResult can be instantiated with unknown tags.
     */
    public function testCanBeInstantiatedWithUnknownTags(): void
    {
        $unknownTags = ['custom_tag', 'legacy_tag'];
        $result = new MigrationResult($unknownTags);

        $this->assertInstanceOf(MigrationResult::class, $result);
    }

    // =========================================================================
    // isSuccess Tests
    // =========================================================================

    /**
     * Test isSuccess returns true when no unknown tags.
     */
    public function testIsSuccessReturnsTrueWhenNoUnknownTags(): void
    {
        $result = new MigrationResult();

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test isSuccess returns true with empty array.
     */
    public function testIsSuccessReturnsTrueWithEmptyArray(): void
    {
        $result = new MigrationResult([]);

        $this->assertTrue($result->isSuccess());
    }

    /**
     * Test isSuccess returns false when unknown tags present.
     */
    public function testIsSuccessReturnsFalseWhenUnknownTagsPresent(): void
    {
        $result = new MigrationResult(['unknown_tag']);

        $this->assertFalse($result->isSuccess());
    }

    // =========================================================================
    // hasWarnings Tests
    // =========================================================================

    /**
     * Test hasWarnings returns false when no unknown tags.
     */
    public function testHasWarningsReturnsFalseWhenNoUnknownTags(): void
    {
        $result = new MigrationResult();

        $this->assertFalse($result->hasWarnings());
    }

    /**
     * Test hasWarnings returns true when unknown tags present.
     */
    public function testHasWarningsReturnsTrueWhenUnknownTagsPresent(): void
    {
        $result = new MigrationResult(['legacy_tag']);

        $this->assertTrue($result->hasWarnings());
    }

    /**
     * Test hasWarnings returns true with multiple unknown tags.
     */
    public function testHasWarningsReturnsTrueWithMultipleUnknownTags(): void
    {
        $result = new MigrationResult(['tag1', 'tag2', 'tag3']);

        $this->assertTrue($result->hasWarnings());
    }

    // =========================================================================
    // getUnknownTags Tests
    // =========================================================================

    /**
     * Test getUnknownTags returns empty array when no tags.
     */
    public function testGetUnknownTagsReturnsEmptyArrayWhenNoTags(): void
    {
        $result = new MigrationResult();

        $this->assertEquals([], $result->getUnknownTags());
    }

    /**
     * Test getUnknownTags returns the unknown tags.
     */
    public function testGetUnknownTagsReturnsTheUnknownTags(): void
    {
        $unknownTags = ['custom_tag', 'legacy_tag'];
        $result = new MigrationResult($unknownTags);

        $this->assertEquals($unknownTags, $result->getUnknownTags());
    }

    /**
     * Test getUnknownTags returns unique tags only.
     */
    public function testGetUnknownTagsReturnsUniqueTags(): void
    {
        $unknownTags = ['tag1', 'tag1', 'tag2', 'tag2'];
        $result = new MigrationResult($unknownTags);

        $tags = $result->getUnknownTags();

        $this->assertCount(2, $tags);
        $this->assertContains('tag1', $tags);
        $this->assertContains('tag2', $tags);
    }

    // =========================================================================
    // getMessage Tests
    // =========================================================================

    /**
     * Test getMessage returns success message when no unknown tags.
     */
    public function testGetMessageReturnsSuccessMessageWhenNoUnknownTags(): void
    {
        $result = new MigrationResult();

        $message = $result->getMessage();

        $this->assertStringContainsString('success', strtolower($message));
    }

    /**
     * Test getMessage returns warning message with tag names.
     */
    public function testGetMessageReturnsWarningMessageWithTagNames(): void
    {
        $result = new MigrationResult(['custom_tag', 'legacy_tag']);

        $message = $result->getMessage();

        $this->assertStringContainsString('custom_tag', $message);
        $this->assertStringContainsString('legacy_tag', $message);
    }

    /**
     * Test getMessage indicates the count of unknown tags.
     */
    public function testGetMessageIndicatesUnknownTagCount(): void
    {
        $result = new MigrationResult(['tag1', 'tag2', 'tag3']);

        $message = $result->getMessage();

        // Message should indicate there are unknown tags
        $this->assertStringContainsString('tag', strtolower($message));
    }
}
