<?php

/**
 * Tests for Install\Uninstaller class.
 *
 * @package SermonBrowser\Tests\Unit\Install
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Install;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Install\Uninstaller;
use Brain\Monkey\Functions;

/**
 * Test class for Uninstaller.
 */
class UninstallerTest extends TestCase
{
    /**
     * Test getTableNames returns all expected table names.
     */
    public function testGetTableNamesReturnsAllTables(): void
    {
        $tables = Uninstaller::getTableNames();

        $this->assertIsArray($tables);
        $this->assertContains('sb_preachers', $tables);
        $this->assertContains('sb_series', $tables);
        $this->assertContains('sb_services', $tables);
        $this->assertContains('sb_sermons', $tables);
        $this->assertContains('sb_stuff', $tables);
        $this->assertContains('sb_books', $tables);
        $this->assertContains('sb_books_sermons', $tables);
        $this->assertContains('sb_sermons_tags', $tables);
        $this->assertContains('sb_tags', $tables);
    }

    /**
     * Test getTableNames returns 9 tables.
     */
    public function testGetTableNamesReturnsCorrectCount(): void
    {
        $tables = Uninstaller::getTableNames();

        $this->assertCount(9, $tables);
    }

    /**
     * Test wipeUploadDirectory is callable.
     */
    public function testWipeUploadDirectoryIsCallable(): void
    {
        $this->assertTrue(method_exists(Uninstaller::class, 'wipeUploadDirectory'));
    }

    /**
     * Test dropTables is callable.
     */
    public function testDropTablesIsCallable(): void
    {
        $this->assertTrue(method_exists(Uninstaller::class, 'dropTables'));
    }

    /**
     * Test deleteOptions is callable.
     */
    public function testDeleteOptionsIsCallable(): void
    {
        $this->assertTrue(method_exists(Uninstaller::class, 'deleteOptions'));
    }

    /**
     * Test run method exists and is static.
     */
    public function testRunMethodExists(): void
    {
        $reflection = new \ReflectionMethod(Uninstaller::class, 'run');

        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isPublic());
    }
}
