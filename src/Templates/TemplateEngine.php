<?php
/**
 * Template Engine for orchestrating template rendering.
 *
 * Loads templates from database options, delegates parsing to TagParser,
 * and provides transient caching for rendered output.
 *
 * @package SermonBrowser\Templates
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Templates;

/**
 * Class TemplateEngine
 *
 * Main entry point for template rendering. Orchestrates loading templates
 * from the database, parsing via TagParser, and caching results.
 */
class TemplateEngine
{
    /**
     * The tag parser instance.
     *
     * @var TagParser
     */
    private TagParser $parser;

    /**
     * Cache duration in seconds (1 hour).
     *
     * @var int
     */
    private const CACHE_DURATION = 3600;

    /**
     * Template option names by type.
     *
     * @var array<string, string>
     */
    private const TEMPLATE_OPTIONS = [
        'search' => 'search_template',
        'single' => 'single_template',
    ];

    /**
     * Constructor.
     *
     * @param TagParser|null $parser The tag parser instance.
     */
    public function __construct(?TagParser $parser = null)
    {
        $this->parser = $parser ?? new TagParser();
    }

    /**
     * Render a template with the given data.
     *
     * Loads the template from database options, checks cache, parses the template,
     * and stores the result in transient cache.
     *
     * @param string $type The template type ('search' or 'single').
     * @param array<string, mixed> $data The data array for template rendering.
     * @param bool $bypassCache Whether to bypass the cache.
     * @return string The rendered HTML.
     * @throws \InvalidArgumentException When template type is invalid.
     */
    public function render(string $type, array $data, bool $bypassCache = false): string
    {
        // Validate template type
        if (!isset(self::TEMPLATE_OPTIONS[$type])) {
            throw new \InvalidArgumentException("Invalid template type: {$type}");
        }

        // Load template from database option
        $template = $this->loadTemplate($type);

        // Generate cache key based on type, template, and data
        $cacheKey = $this->generateCacheKey($type, $template, $data);

        // Check cache unless bypassing
        if (!$bypassCache) {
            $cached = get_transient($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }

        // Parse the template
        $rendered = $this->parser->parse($template, $data, $type);

        // Store in cache
        set_transient($cacheKey, $rendered, self::CACHE_DURATION);

        return $rendered;
    }

    /**
     * Load template from database option.
     *
     * @param string $type The template type.
     * @return string The template string.
     */
    private function loadTemplate(string $type): string
    {
        $optionName = self::TEMPLATE_OPTIONS[$type];
        $template = sb_get_option($optionName);

        return $template ?? '';
    }

    /**
     * Generate a cache key for the template and data combination.
     *
     * @param string $type The template type.
     * @param string $template The template string.
     * @param array<string, mixed> $data The data array.
     * @return string The cache key.
     */
    private function generateCacheKey(string $type, string $template, array $data): string
    {
        $hash = md5($template . serialize($data));
        return "sb_template_{$type}_{$hash}";
    }

    /**
     * Clear all template caches.
     *
     * Deletes all transients with the sb_template_ prefix.
     *
     * @return int The number of transients deleted.
     */
    public function clearCache(): int
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '_transient_sb_template_%'
        );

        return (int) $wpdb->query($sql);
    }
}
