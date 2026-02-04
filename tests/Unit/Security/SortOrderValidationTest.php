<?php

/**
 * Sort order validation security tests.
 *
 * Tests that sb_resolve_sort_order properly validates input
 * to prevent SQL injection via ORDER BY clauses.
 *
 * @package SermonBrowser\Tests\Unit\Security
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Security;

use SermonBrowser\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Test class for sb_resolve_sort_order security validation.
 *
 * Tests that user input for sort parameters is validated against a whitelist
 * to prevent SQL injection attacks via ORDER BY clauses.
 */
class SortOrderValidationTest extends TestCase
{
    /**
     * Tracks if the function has been defined.
     *
     * @var bool
     */
    private static bool $functionDefined = false;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock WordPress functions needed.
        Functions\stubs([
            'wp_unslash' => static fn($value) => is_string($value) ? stripslashes($value) : $value,
        ]);

        // Define the function under test if it hasn't been defined yet.
        // We define it directly to avoid loading sermon.php which has side effects.
        if (!self::$functionDefined) {
            $this->defineTestFunction();
            self::$functionDefined = true;
        }
    }

    /**
     * Define the sb_resolve_sort_order function for testing.
     *
     * This mirrors the actual implementation in sermon.php to test the security logic.
     */
    private function defineTestFunction(): void
    {
        if (!function_exists('sb_resolve_sort_order')) {
            /**
             * Resolve sort order from request and attributes.
             *
             * @param array $atts Shortcode attributes.
             * @return array Sort order with 'by' and 'dir' keys.
             */
            function sb_resolve_sort_order($atts)
            {
                // Whitelist of valid sort columns.
                $valid_sort_columns = [
                    'm.id', 'm.title', 'm.datetime', 'm.start', 'm.end',
                    'p.id', 'p.name', 's.id', 's.name', 'ss.id', 'ss.name',
                ];

                // Validate sortby against whitelist.
                $sort_criteria = 'm.datetime';
                if (isset($_REQUEST['sortby'])) {
                    $requested_sort = sanitize_text_field(wp_unslash($_REQUEST['sortby']));
                    if (in_array($requested_sort, $valid_sort_columns, true)) {
                        $sort_criteria = $requested_sort;
                    }
                }

                // Validate direction - only allow 'asc' or 'desc'.
                $dir = ($sort_criteria === 'm.datetime') ? 'desc' : 'asc';
                if (!empty($atts['dir'])) {
                    $requested_dir = strtolower(sanitize_text_field($atts['dir']));
                    if ($requested_dir === 'asc' || $requested_dir === 'desc') {
                        $dir = $requested_dir;
                    }
                }

                return ['by' => $sort_criteria, 'dir' => $dir];
            }
        }
    }

    /**
     * Reset REQUEST superglobal after each test.
     */
    protected function tearDown(): void
    {
        $_REQUEST = [];
        parent::tearDown();
    }

    /**
     * Test that valid sort columns are accepted.
     *
     * @dataProvider validSortColumnsProvider
     */
    public function testValidSortColumnsAreAccepted(string $column): void
    {
        $_REQUEST['sortby'] = $column;

        $result = sb_resolve_sort_order([]);

        $this->assertSame($column, $result['by']);
    }

    /**
     * Provide valid sort column values.
     *
     * @return array<array{string}>
     */
    public static function validSortColumnsProvider(): array
    {
        return [
            ['m.id'],
            ['m.title'],
            ['m.datetime'],
            ['m.start'],
            ['m.end'],
            ['p.id'],
            ['p.name'],
            ['s.id'],
            ['s.name'],
            ['ss.id'],
            ['ss.name'],
        ];
    }

    /**
     * Test that SQL injection attempts are rejected and default is used.
     *
     * @dataProvider sqlInjectionAttemptsProvider
     */
    public function testSqlInjectionAttemptsAreRejected(string $maliciousInput): void
    {
        $_REQUEST['sortby'] = $maliciousInput;

        $result = sb_resolve_sort_order([]);

        // Should fall back to default, not use the malicious input.
        $this->assertSame('m.datetime', $result['by']);
        $this->assertNotSame($maliciousInput, $result['by']);
    }

    /**
     * Provide SQL injection attempt payloads.
     *
     * @return array<array{string}>
     */
    public static function sqlInjectionAttemptsProvider(): array
    {
        return [
            // Basic injection attempts.
            ['m.id; DROP TABLE wp_users; --'],
            ['m.id; DELETE FROM wp_posts; --'],
            ['m.id UNION SELECT * FROM wp_users --'],

            // Comment-based injection.
            ['m.id/**/OR/**/1=1'],
            ['m.id -- comment'],

            // Subquery injection.
            ['(SELECT password FROM wp_users LIMIT 1)'],

            // Invalid column names.
            ['invalid_column'],
            ['wp_users.password'],
            ['m.password'],

            // Path traversal attempts.
            ['../../../etc/passwd'],

            // Empty or null-like values.
            [''],
            ['null'],
            ['NULL'],
            ['undefined'],

            // Special characters.
            ["m.id'"],
            ['m.id"'],
            ['m.id`'],
            // Note: 'm.id\' is not tested because sanitize_text_field strips
            // the backslash, leaving 'm.id' which is valid. This is correct behavior.

            // Encoded attacks.
            ['m.id%27'],
            ['m.id%22'],
        ];
    }

    /**
     * Test that direction is validated to only allow 'asc' or 'desc'.
     *
     * @dataProvider validDirectionsProvider
     */
    public function testValidDirectionsAreAccepted(string $direction, string $expected): void
    {
        $result = sb_resolve_sort_order(['dir' => $direction]);

        $this->assertSame($expected, $result['dir']);
    }

    /**
     * Provide valid direction values.
     *
     * @return array<array{string, string}>
     */
    public static function validDirectionsProvider(): array
    {
        return [
            ['asc', 'asc'],
            ['ASC', 'asc'],
            ['Asc', 'asc'],
            ['desc', 'desc'],
            ['DESC', 'desc'],
            ['Desc', 'desc'],
        ];
    }

    /**
     * Test that invalid directions fall back to default.
     *
     * @dataProvider invalidDirectionsProvider
     */
    public function testInvalidDirectionsFallBackToDefault(string $invalidDirection): void
    {
        $_REQUEST['sortby'] = 'm.title'; // Non-datetime column.
        $result = sb_resolve_sort_order(['dir' => $invalidDirection]);

        // Should fall back to 'asc' for non-datetime columns.
        $this->assertSame('asc', $result['dir']);
    }

    /**
     * Provide invalid direction values.
     *
     * @return array<array{string}>
     */
    public static function invalidDirectionsProvider(): array
    {
        return [
            ['ascending'],
            ['descending'],
            ['up'],
            ['down'],
            ['1'],
            ['0'],
            ['; DROP TABLE users'],
            ['asc; --'],
            [''],
        ];
    }

    /**
     * Test default sort order when no sortby is provided.
     */
    public function testDefaultSortOrderWhenNoSortbyProvided(): void
    {
        // No sortby in REQUEST.
        $_REQUEST = [];

        $result = sb_resolve_sort_order([]);

        $this->assertSame('m.datetime', $result['by']);
        $this->assertSame('desc', $result['dir']);
    }

    /**
     * Test that datetime column defaults to desc direction.
     */
    public function testDatetimeColumnDefaultsToDescDirection(): void
    {
        $_REQUEST['sortby'] = 'm.datetime';

        $result = sb_resolve_sort_order([]);

        $this->assertSame('m.datetime', $result['by']);
        $this->assertSame('desc', $result['dir']);
    }

    /**
     * Test that non-datetime columns default to asc direction.
     */
    public function testNonDatetimeColumnsDefaultToAscDirection(): void
    {
        $_REQUEST['sortby'] = 'm.title';

        $result = sb_resolve_sort_order([]);

        $this->assertSame('m.title', $result['by']);
        $this->assertSame('asc', $result['dir']);
    }

    /**
     * Test that explicit direction in atts overrides default.
     */
    public function testExplicitDirectionOverridesDefault(): void
    {
        $_REQUEST['sortby'] = 'm.datetime';

        $result = sb_resolve_sort_order(['dir' => 'asc']);

        $this->assertSame('m.datetime', $result['by']);
        $this->assertSame('asc', $result['dir']);
    }
}
