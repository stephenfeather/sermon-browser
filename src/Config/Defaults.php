<?php

declare(strict_types=1);

namespace SermonBrowser\Config;

/**
 * Provides default values for SermonBrowser.
 */
class Defaults
{
    /**
     * Get a default value by type.
     *
     * @param string $type The type of default value.
     * @return mixed The default value or null if not found.
     */
    public static function get(string $type): mixed
    {
        return match ($type) {
            'sermon_path' => self::getSermonPath(),
            'attachment_url' => self::getAttachmentUrl(),
            'bible_books' => self::getBibleBooks(),
            'eng_bible_books' => self::getEnglishBibleBooks(),
            default => null,
        };
    }

    /**
     * Get the default sermon upload path.
     */
    private static function getSermonPath(): string
    {
        $upload_path = wp_upload_dir();
        $upload_path = $upload_path['basedir'];
        if (substr($upload_path, 0, strlen(ABSPATH)) === ABSPATH) {
            $upload_path = substr($upload_path, strlen(ABSPATH));
        }
        return trailingslashit($upload_path) . 'sermons/';
    }

    /**
     * Get the default attachment URL.
     */
    private static function getAttachmentUrl(): string
    {
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['baseurl'];
        return trailingslashit($upload_dir) . 'sermons/';
    }

    /**
     * Get translated Bible book names.
     *
     * @return array<string>
     */
    private static function getBibleBooks(): array
    {
        return [
            __('Genesis', 'sermon-browser'),
            __('Exodus', 'sermon-browser'),
            __('Leviticus', 'sermon-browser'),
            __('Numbers', 'sermon-browser'),
            __('Deuteronomy', 'sermon-browser'),
            __('Joshua', 'sermon-browser'),
            __('Judges', 'sermon-browser'),
            __('Ruth', 'sermon-browser'),
            __('1 Samuel', 'sermon-browser'),
            __('2 Samuel', 'sermon-browser'),
            __('1 Kings', 'sermon-browser'),
            __('2 Kings', 'sermon-browser'),
            __('1 Chronicles', 'sermon-browser'),
            __('2 Chronicles', 'sermon-browser'),
            __('Ezra', 'sermon-browser'),
            __('Nehemiah', 'sermon-browser'),
            __('Esther', 'sermon-browser'),
            __('Job', 'sermon-browser'),
            __('Psalm', 'sermon-browser'),
            __('Proverbs', 'sermon-browser'),
            __('Ecclesiastes', 'sermon-browser'),
            __('Song of Solomon', 'sermon-browser'),
            __('Isaiah', 'sermon-browser'),
            __('Jeremiah', 'sermon-browser'),
            __('Lamentations', 'sermon-browser'),
            __('Ezekiel', 'sermon-browser'),
            __('Daniel', 'sermon-browser'),
            __('Hosea', 'sermon-browser'),
            __('Joel', 'sermon-browser'),
            __('Amos', 'sermon-browser'),
            __('Obadiah', 'sermon-browser'),
            __('Jonah', 'sermon-browser'),
            __('Micah', 'sermon-browser'),
            __('Nahum', 'sermon-browser'),
            __('Habakkuk', 'sermon-browser'),
            __('Zephaniah', 'sermon-browser'),
            __('Haggai', 'sermon-browser'),
            __('Zechariah', 'sermon-browser'),
            __('Malachi', 'sermon-browser'),
            __('Matthew', 'sermon-browser'),
            __('Mark', 'sermon-browser'),
            __('Luke', 'sermon-browser'),
            __('John', 'sermon-browser'),
            __('Acts', 'sermon-browser'),
            __('Romans', 'sermon-browser'),
            __('1 Corinthians', 'sermon-browser'),
            __('2 Corinthians', 'sermon-browser'),
            __('Galatians', 'sermon-browser'),
            __('Ephesians', 'sermon-browser'),
            __('Philippians', 'sermon-browser'),
            __('Colossians', 'sermon-browser'),
            __('1 Thessalonians', 'sermon-browser'),
            __('2 Thessalonians', 'sermon-browser'),
            __('1 Timothy', 'sermon-browser'),
            __('2 Timothy', 'sermon-browser'),
            __('Titus', 'sermon-browser'),
            __('Philemon', 'sermon-browser'),
            __('Hebrews', 'sermon-browser'),
            __('James', 'sermon-browser'),
            __('1 Peter', 'sermon-browser'),
            __('2 Peter', 'sermon-browser'),
            __('1 John', 'sermon-browser'),
            __('2 John', 'sermon-browser'),
            __('3 John', 'sermon-browser'),
            __('Jude', 'sermon-browser'),
            __('Revelation', 'sermon-browser'),
        ];
    }

    /**
     * Get English Bible book names (untranslated).
     *
     * @return array<string>
     */
    private static function getEnglishBibleBooks(): array
    {
        return [
            'Genesis', 'Exodus', 'Leviticus', 'Numbers', 'Deuteronomy',
            'Joshua', 'Judges', 'Ruth', '1 Samuel', '2 Samuel',
            '1 Kings', '2 Kings', '1 Chronicles', '2 Chronicles',
            'Ezra', 'Nehemiah', 'Esther', 'Job', 'Psalm', 'Proverbs',
            'Ecclesiastes', 'Song of Solomon', 'Isaiah', 'Jeremiah',
            'Lamentations', 'Ezekiel', 'Daniel', 'Hosea', 'Joel', 'Amos',
            'Obadiah', 'Jonah', 'Micah', 'Nahum', 'Habakkuk', 'Zephaniah',
            'Haggai', 'Zechariah', 'Malachi', 'Matthew', 'Mark', 'Luke',
            'John', 'Acts', 'Romans', '1 Corinthians', '2 Corinthians',
            'Galatians', 'Ephesians', 'Philippians', 'Colossians',
            '1 Thessalonians', '2 Thessalonians', '1 Timothy', '2 Timothy',
            'Titus', 'Philemon', 'Hebrews', 'James', '1 Peter', '2 Peter',
            '1 John', '2 John', '3 John', 'Jude', 'Revelation',
        ];
    }
}
