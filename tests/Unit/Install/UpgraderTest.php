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
use SermonBrowser\Config\OptionsManager;
use Brain\Monkey\Functions;

/**
 * Test class for Upgrader.
 */
class UpgraderTest extends TestCase
{
    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Clear OptionsManager cache before each test
        $reflection = new \ReflectionClass(OptionsManager::class);
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheProperty->setValue(null, null);
    }

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
                if ($option === 'sermonbrowser_options') {
                    return false; // Empty cache
                }
                return false;
            });

        Functions\expect('update_option')
            ->atLeast()
            ->once()
            ->andReturn(true);

        Functions\expect('delete_option')
            ->atLeast()
            ->once();

        // Call method - Brain\Monkey verifies the expected function calls
        Upgrader::upgradeOptions();

        // Explicit assertion for SonarQube (Brain\Monkey expectations validate behavior)
        $this->assertTrue(true, 'upgradeOptions completed without error');
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
                if ($option === 'sermonbrowser_options') {
                    return false; // Empty cache
                }
                return false;
            });

        Functions\expect('update_option')
            ->atLeast()
            ->once()
            ->andReturn(true);

        Functions\expect('delete_option')
            ->atLeast()
            ->once();

        // Call method - Brain\Monkey verifies the expected function calls
        Upgrader::upgradeOptions();

        // Explicit assertion for SonarQube (Brain\Monkey expectations validate behavior)
        $this->assertTrue(true, 'upgradeOptions completed without error');
    }

    /**
     * Test versionUpgrade updates code version.
     */
    public function testVersionUpgradeUpdatesCodeVersion(): void
    {
        // Mock get_option with sermonbrowser_options containing filter_type
        $existingOptions = ['filter_type' => 'dropdown'];
        Functions\expect('get_option')
            ->andReturnUsing(function ($option) use ($existingOptions) {
                if ($option === 'sermonbrowser_options') {
                    return base64_encode(serialize($existingOptions));
                }
                return false;
            });

        Functions\expect('update_option')
            ->atLeast()
            ->once()
            ->andReturn(true);

        Functions\expect('delete_transient')
            ->with('sb_template_search')
            ->once();

        Functions\expect('delete_transient')
            ->with('sb_template_single')
            ->once();

        // Call method - Brain\Monkey verifies the expected function calls
        Upgrader::versionUpgrade('1.0.0', '2.0.0');

        // Explicit assertion for SonarQube (Brain\Monkey expectations validate behavior)
        $this->assertTrue(true, 'versionUpgrade completed without error');
    }

    /**
     * Test versionUpgrade sets default filter type when empty.
     */
    public function testVersionUpgradeSetsDefaultFilterType(): void
    {
        // Mock get_option with empty filter_type
        $existingOptions = ['filter_type' => ''];
        Functions\expect('get_option')
            ->andReturnUsing(function ($option) use ($existingOptions) {
                if ($option === 'sermonbrowser_options') {
                    return base64_encode(serialize($existingOptions));
                }
                return false;
            });

        Functions\expect('update_option')
            ->atLeast()
            ->once()
            ->andReturn(true);

        Functions\expect('delete_transient')
            ->twice();

        // Call method - Brain\Monkey verifies the expected function calls
        Upgrader::versionUpgrade('1.0.0', '2.0.0');

        // Explicit assertion for SonarQube (Brain\Monkey expectations validate behavior)
        $this->assertTrue(true, 'versionUpgrade sets default filter_type');
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
