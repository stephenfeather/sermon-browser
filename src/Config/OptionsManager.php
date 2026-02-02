<?php

declare(strict_types=1);

namespace SermonBrowser\Config;

/**
 * Manages SermonBrowser options storage.
 *
 * Options are stored in a single serialized row in wp_options for efficiency.
 * Large options (templates, CSS) are stored in individual rows.
 */
class OptionsManager
{
    /**
     * Cached options array.
     */
    private static ?array $cache = null;

    /**
     * Get a SermonBrowser option value.
     *
     * @param string $key Option key.
     * @return mixed Option value or empty string if not found.
     */
    public static function get(string $key): mixed
    {
        if (in_array($key, self::specialOptionNames(), true)) {
            return stripslashes(base64_decode(get_option("sermonbrowser_{$key}") ?: ''));
        }

        self::loadCache();

        return self::$cache[$key] ?? '';
    }

    /**
     * Update a SermonBrowser option value.
     *
     * @param string $key   Option key.
     * @param mixed  $value Option value.
     * @return bool True if option was updated, false otherwise.
     */
    public static function update(string $key, mixed $value): bool
    {
        if (in_array($key, self::specialOptionNames(), true)) {
            return update_option("sermonbrowser_{$key}", base64_encode($value));
        }

        self::loadCache();

        if (!isset(self::$cache[$key]) || self::$cache[$key] !== $value) {
            self::$cache[$key] = $value;
            return update_option('sermonbrowser_options', base64_encode(serialize(self::$cache)));
        }

        return false;
    }

    /**
     * Get option names that need individual storage (large strings).
     *
     * @return array<string> List of special option names.
     */
    public static function specialOptionNames(): array
    {
        return ['single_template', 'search_template', 'css_style'];
    }

    /**
     * Load options cache from database.
     */
    private static function loadCache(): void
    {
        if (self::$cache !== null) {
            return;
        }

        $options = get_option('sermonbrowser_options');
        if ($options === false) {
            self::$cache = [];
            return;
        }

        $decoded = base64_decode($options);
        $unserialized = unserialize($decoded, ['allowed_classes' => false]);

        if ($unserialized === false) {
            wp_die('Failed to get SermonBrowser options ' . $decoded);
        }

        self::$cache = $unserialized;
    }

    /**
     * Clear the options cache (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
