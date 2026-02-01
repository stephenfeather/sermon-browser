<?php

declare(strict_types=1);

namespace SermonBrowser\Config;

/**
 * File type configuration for Sermon Browser.
 *
 * Provides mapping of file extensions to their display names, icons, and MIME types.
 *
 * @since 1.0.0
 */
class FileTypes
{
    /**
     * Default icon for unknown file types.
     */
    private const DEFAULT_FILE_ICON = 'unknown.png';

    /**
     * Default icon for site/URL references.
     */
    private const DEFAULT_SITE_ICON = 'url.png';

    /**
     * Get all supported file types.
     *
     * @return array<string, array{name: string, icon: string, content-type: string}>
     */
    public static function getTypes(): array
    {
        return [
            'mp3' => [
                'name' => 'mp3',
                'icon' => 'audio.png',
                'content-type' => 'audio/mpeg',
            ],
            'doc' => [
                'name' => 'Microsoft Word',
                'icon' => 'doc.png',
                'content-type' => 'application/ms-word',
            ],
            'docx' => [
                'name' => 'Microsoft Word',
                'icon' => 'doc.png',
                'content-type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'rtf' => [
                'name' => 'Rich Text Format',
                'icon' => 'doc.png',
                'content-type' => 'application/rtf',
            ],
            'ppt' => [
                'name' => 'Powerpoint',
                'icon' => 'ppt.png',
                'content-type' => 'application/vnd.ms-powerpoint',
            ],
            'pptx' => [
                'name' => 'Powerpoint',
                'icon' => 'ppt.png',
                'content-type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ],
            'pdf' => [
                'name' => 'Adobe Acrobat',
                'icon' => 'pdf.png',
                'content-type' => 'application/pdf',
            ],
            'iso' => [
                'name' => 'Disk image',
                'icon' => 'iso.png',
                'content-type' => 'application/octet-stream',
            ],
            'wma' => [
                'name' => 'Windows Media Audio',
                'icon' => 'audio.png',
                'content-type' => 'audio/x-ms-wma',
            ],
            'txt' => [
                'name' => 'Text',
                'icon' => 'txt.png',
                'content-type' => 'text/plain',
            ],
            'wmv' => [
                'name' => 'Windows Media Video',
                'icon' => 'video.png',
                'content-type' => 'video/x-ms-wmv',
            ],
            'mov' => [
                'name' => 'Quicktime Video',
                'icon' => 'video.png',
                'content-type' => 'video/quicktime',
            ],
            'divx' => [
                'name' => 'DivX Video',
                'icon' => 'video.png',
                'content-type' => 'video/divx',
            ],
            'avi' => [
                'name' => 'Video',
                'icon' => 'video.png',
                'content-type' => 'video/x-msvideo',
            ],
            'xls' => [
                'name' => 'Microsoft Excel',
                'icon' => 'xls.png',
                'content-type' => 'application/vnd.ms-excel',
            ],
            'xlsx' => [
                'name' => 'Microsoft Excel',
                'icon' => 'xls.png',
                'content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'zip' => [
                'name' => 'Zip file',
                'icon' => 'zip.png',
                'content-type' => 'application/zip',
            ],
            'gz' => [
                'name' => 'Gzip file',
                'icon' => 'zip.png',
                'content-type' => 'application/x-gzip',
            ],
        ];
    }

    /**
     * Get the MIME type for a file extension.
     *
     * @param string $extension The file extension (without dot).
     * @return string The MIME type, or 'application/octet-stream' if unknown.
     */
    public static function getMimeType(string $extension): string
    {
        $types = self::getTypes();
        $ext = strtolower($extension);

        if (isset($types[$ext])) {
            return $types[$ext]['content-type'];
        }

        return 'application/octet-stream';
    }

    /**
     * Get the icon filename for a file extension.
     *
     * @param string $extension The file extension (without dot).
     * @return string The icon filename.
     */
    public static function getIcon(string $extension): string
    {
        $types = self::getTypes();
        $ext = strtolower($extension);

        if (isset($types[$ext])) {
            return $types[$ext]['icon'];
        }

        return self::DEFAULT_FILE_ICON;
    }

    /**
     * Get the display name for a file extension.
     *
     * @param string $extension The file extension (without dot).
     * @return string The display name, or the extension if unknown.
     */
    public static function getName(string $extension): string
    {
        $types = self::getTypes();
        $ext = strtolower($extension);

        if (isset($types[$ext])) {
            return $types[$ext]['name'];
        }

        return $extension;
    }

    /**
     * Get site icons mapping.
     *
     * @return array<string, string>
     */
    public static function getSiteIcons(): array
    {
        return [
            'https://google.com' => 'url.png',
        ];
    }

    /**
     * Get the default file icon.
     *
     * @return string
     */
    public static function getDefaultFileIcon(): string
    {
        return self::DEFAULT_FILE_ICON;
    }

    /**
     * Get the default site icon.
     *
     * @return string
     */
    public static function getDefaultSiteIcon(): string
    {
        return self::DEFAULT_SITE_ICON;
    }
}
