<?php

/**
 * Tests for Config\FileTypes class.
 *
 * @package SermonBrowser\Tests\Unit\Config
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Config;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Config\FileTypes;

/**
 * Test class for FileTypes.
 */
class FileTypesTest extends TestCase
{
    /**
     * Test getTypes returns an array.
     */
    public function testGetTypesReturnsArray(): void
    {
        $types = FileTypes::getTypes();

        $this->assertIsArray($types);
    }

    /**
     * Test getTypes includes common file types.
     */
    public function testGetTypesIncludesCommonTypes(): void
    {
        $types = FileTypes::getTypes();

        $this->assertArrayHasKey('mp3', $types);
        $this->assertArrayHasKey('pdf', $types);
        $this->assertArrayHasKey('doc', $types);
        $this->assertArrayHasKey('docx', $types);
    }

    /**
     * Test file type structure is correct.
     */
    public function testFileTypeStructure(): void
    {
        $types = FileTypes::getTypes();
        $mp3 = $types['mp3'];

        $this->assertArrayHasKey('name', $mp3);
        $this->assertArrayHasKey('icon', $mp3);
        $this->assertArrayHasKey('content-type', $mp3);
    }

    /**
     * Test getMimeType returns correct type for known extension.
     */
    public function testGetMimeTypeForKnownExtension(): void
    {
        $mimeType = FileTypes::getMimeType('mp3');

        $this->assertEquals('audio/mpeg', $mimeType);
    }

    /**
     * Test getMimeType returns correct type for PDF.
     */
    public function testGetMimeTypeForPdf(): void
    {
        $mimeType = FileTypes::getMimeType('pdf');

        $this->assertEquals('application/pdf', $mimeType);
    }

    /**
     * Test getMimeType returns octet-stream for unknown extension.
     */
    public function testGetMimeTypeForUnknownExtension(): void
    {
        $mimeType = FileTypes::getMimeType('xyz');

        $this->assertEquals('application/octet-stream', $mimeType);
    }

    /**
     * Test getMimeType is case insensitive.
     */
    public function testGetMimeTypeIsCaseInsensitive(): void
    {
        $mimeType1 = FileTypes::getMimeType('MP3');
        $mimeType2 = FileTypes::getMimeType('Mp3');

        $this->assertEquals('audio/mpeg', $mimeType1);
        $this->assertEquals('audio/mpeg', $mimeType2);
    }

    /**
     * Test getIcon returns correct icon for known extension.
     */
    public function testGetIconForKnownExtension(): void
    {
        $icon = FileTypes::getIcon('mp3');

        $this->assertEquals('audio.png', $icon);
    }

    /**
     * Test getIcon returns default icon for unknown extension.
     */
    public function testGetIconForUnknownExtension(): void
    {
        $icon = FileTypes::getIcon('xyz');

        $this->assertEquals('unknown.png', $icon);
    }

    /**
     * Test getName returns correct name for known extension.
     */
    public function testGetNameForKnownExtension(): void
    {
        $name = FileTypes::getName('pdf');

        $this->assertEquals('Adobe Acrobat', $name);
    }

    /**
     * Test getName returns extension for unknown extension.
     */
    public function testGetNameForUnknownExtension(): void
    {
        $name = FileTypes::getName('xyz');

        $this->assertEquals('xyz', $name);
    }

    /**
     * Test getSiteIcons returns array.
     */
    public function testGetSiteIconsReturnsArray(): void
    {
        $icons = FileTypes::getSiteIcons();

        $this->assertIsArray($icons);
    }

    /**
     * Test getDefaultFileIcon returns correct value.
     */
    public function testGetDefaultFileIcon(): void
    {
        $icon = FileTypes::getDefaultFileIcon();

        $this->assertEquals('unknown.png', $icon);
    }

    /**
     * Test getDefaultSiteIcon returns correct value.
     */
    public function testGetDefaultSiteIcon(): void
    {
        $icon = FileTypes::getDefaultSiteIcon();

        $this->assertEquals('url.png', $icon);
    }

    /**
     * Test video file types are included.
     */
    public function testVideoFileTypesIncluded(): void
    {
        $types = FileTypes::getTypes();

        $this->assertArrayHasKey('wmv', $types);
        $this->assertArrayHasKey('mov', $types);
        $this->assertArrayHasKey('avi', $types);
    }

    /**
     * Test office file types are included.
     */
    public function testOfficeFileTypesIncluded(): void
    {
        $types = FileTypes::getTypes();

        $this->assertArrayHasKey('xls', $types);
        $this->assertArrayHasKey('xlsx', $types);
        $this->assertArrayHasKey('ppt', $types);
        $this->assertArrayHasKey('pptx', $types);
    }
}
