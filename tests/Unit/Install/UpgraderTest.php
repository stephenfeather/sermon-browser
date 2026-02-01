<?php

/**
 * Tests for Install\Upgrader class.
 *
 * @package SermonBrowser\Tests\Unit\Install
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Install;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Install\Upgrader;
use Brain\Monkey\Functions;

/**
 * Test class for Upgrader.
 */
class UpgraderTest extends TestCase
{
    /**
     * Test upgradeOptions processes standard options correctly.
     */
    public function testUpgradeOptionsProcessesStandardOptions(): void
    {
        // Mock get_option to return value for sb_podcast only
        Functions\expect('get_option')
            ->andReturnUsing(function ($option) {
                if ($option === 'sb_podcast') {
                    return 'http://example.com/podcast';
                }
                return false;
            });

        Functions\expect('sb_update_option')
            ->atLeast()
            ->once();

        Functions\expect('delete_option')
            ->atLeast()
            ->once();

        Upgrader::upgradeOptions();
    }

    /**
     * Test upgradeOptions processes base64 encoded options correctly.
     */
    public function testUpgradeOptionsProcessesBase64Options(): void
    {
        // All standard options return false, except css_style
        Functions\expect('get_option')
            ->andReturnUsing(function ($option) {
                if ($option === 'sb_sermon_style') {
                    return base64_encode(addslashes('div { color: red; }'));
                }
                return false;
            });

        Functions\expect('sb_update_option')
            ->atLeast()
            ->once();

        Functions\expect('delete_option')
            ->atLeast()
            ->once();

        Upgrader::upgradeOptions();
    }

    /**
     * Test versionUpgrade updates code version.
     */
    public function testVersionUpgradeUpdatesCodeVersion(): void
    {
        Functions\expect('sb_update_option')
            ->with('code_version', '2.0.0')
            ->once();

        Functions\expect('sb_get_option')
            ->with('filter_type')
            ->andReturn('dropdown');

        Functions\expect('delete_transient')
            ->with('sb_template_search')
            ->once();

        Functions\expect('delete_transient')
            ->with('sb_template_single')
            ->once();

        Upgrader::versionUpgrade('1.0.0', '2.0.0');
    }

    /**
     * Test versionUpgrade sets default filter type when empty.
     */
    public function testVersionUpgradeSetsDefaultFilterType(): void
    {
        Functions\expect('sb_update_option')
            ->with('code_version', '2.0.0')
            ->once();

        Functions\expect('sb_get_option')
            ->with('filter_type')
            ->andReturn('');

        Functions\expect('sb_update_option')
            ->with('filter_type', 'dropdown')
            ->once();

        Functions\expect('delete_transient')
            ->twice();

        Upgrader::versionUpgrade('1.0.0', '2.0.0');
    }

    /**
     * Test getStandardOptionMappings returns correct array structure.
     */
    public function testGetStandardOptionMappingsReturnsCorrectStructure(): void
    {
        $mappings = Upgrader::getStandardOptionMappings();

        $this->assertIsArray($mappings);
        $this->assertNotEmpty($mappings);

        // Check structure of first mapping
        $first = $mappings[0];
        $this->assertArrayHasKey('old_option', $first);
        $this->assertArrayHasKey('new_option', $first);
    }

    /**
     * Test getBase64OptionMappings returns correct array structure.
     */
    public function testGetBase64OptionMappingsReturnsCorrectStructure(): void
    {
        $mappings = Upgrader::getBase64OptionMappings();

        $this->assertIsArray($mappings);
        $this->assertNotEmpty($mappings);

        // Check that css_style mapping exists
        $found = false;
        foreach ($mappings as $mapping) {
            if ($mapping['new_option'] === 'css_style') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'css_style mapping should exist');
    }
}
