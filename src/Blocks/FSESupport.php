<?php

/**
 * Full Site Editing (FSE) Support
 *
 * Registers block templates and template parts for FSE themes.
 * Provides theme.json integration and Query Loop compatibility.
 *
 * @package SermonBrowser\Blocks
 * @since 0.8.0
 */

declare(strict_types=1);

namespace SermonBrowser\Blocks;

/**
 * Class FSESupport
 *
 * Handles Full Site Editing integration for Sermon Browser.
 */
class FSESupport
{
    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Plugin templates directory path.
     *
     * @var string
     */
    private string $templatesPath;

    /**
     * Plugin parts directory path.
     *
     * @var string
     */
    private string $partsPath;

    /**
     * Private constructor for singleton pattern.
     */
    private function __construct()
    {
        $this->templatesPath = SB_PLUGIN_DIR . '/sermon-browser/src/Blocks/templates';
        $this->partsPath = SB_PLUGIN_DIR . '/sermon-browser/src/Blocks/parts';
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Initialize FSE support by hooking into WordPress.
     *
     * @return void
     */
    public function init(): void
    {
        // Register block template parts.
        add_action('init', [$this, 'registerTemplateParts']);

        // Add custom theme.json settings.
        add_filter('wp_theme_json_data_theme', [$this, 'addThemeJsonSettings']);

        // Add allowed block types for Query Loop.
        add_filter('allowed_block_types_all', [$this, 'allowBlocksInQueryLoop'], 10, 2);

        // Register block templates for archive display.
        add_filter('get_block_templates', [$this, 'addBlockTemplates'], 10, 3);

        // Make template parts available.
        add_filter('get_block_file_template', [$this, 'getBlockFileTemplate'], 10, 3);
    }

    /**
     * Register block template parts with WordPress.
     *
     * @return void
     */
    public function registerTemplateParts(): void
    {
        // Only register if block templates are supported.
        if (!wp_is_block_theme()) {
            return;
        }

        // Register the template part area for sermons.
        add_filter('default_wp_template_part_areas', function (array $areas): array {
            $areas[] = [
                'area'        => 'sermon-browser',
                'label'       => __('Sermon Browser', 'sermon-browser'),
                'description' => __('Sermon Browser template parts for displaying sermons.', 'sermon-browser'),
                'icon'        => 'book',
                'area_tag'    => 'section',
            ];
            return $areas;
        });
    }

    /**
     * Add custom theme.json settings for Sermon Browser.
     *
     * @param \WP_Theme_JSON_Data $theme_json The theme JSON data object.
     * @return \WP_Theme_JSON_Data Modified theme JSON data.
     */
    public function addThemeJsonSettings($theme_json)
    {
        // Get existing data.
        $data = $theme_json->get_data();

        // Add sermon-browser custom settings.
        $sermon_settings = [
            'settings' => [
                'custom' => [
                    'sermon-browser' => [
                        'primaryColor'  => 'var(--wp--preset--color--primary, #0073aa)',
                        'accentColor'   => 'var(--wp--preset--color--secondary, #005a87)',
                        'playerStyle'   => 'default',
                        'cardRadius'    => '8px',
                        'cardShadow'    => '0 2px 4px rgba(0, 0, 0, 0.1)',
                        'filterStyle'   => 'pills',
                        'gridGap'       => 'var(--wp--preset--spacing--30, 1.5rem)',
                    ],
                ],
            ],
            'styles' => [
                'blocks' => [
                    'sermon-browser/sermon-list' => [
                        'spacing' => [
                            'blockGap' => 'var(--wp--custom--sermon-browser--grid-gap)',
                        ],
                    ],
                    'sermon-browser/sermon-grid' => [
                        'spacing' => [
                            'blockGap' => 'var(--wp--custom--sermon-browser--grid-gap)',
                        ],
                    ],
                ],
            ],
        ];

        // Merge with existing data.
        $merged_data = array_replace_recursive($data, $sermon_settings);

        return $theme_json->update_with($merged_data);
    }

    /**
     * Allow Sermon Browser blocks in Query Loop.
     *
     * @param array|bool                    $allowed_blocks Allowed block types.
     * @param \WP_Block_Editor_Context|null $context        Editor context.
     * @return array|bool Modified allowed blocks.
     */
    public function allowBlocksInQueryLoop($allowed_blocks, $context)
    {
        // If all blocks are allowed, return as-is.
        if ($allowed_blocks === true) {
            return true;
        }

        // Get our block names.
        $sermon_blocks = [
            'sermon-browser/tag-cloud',
            'sermon-browser/single-sermon',
            'sermon-browser/sermon-list',
            'sermon-browser/preacher-list',
            'sermon-browser/series-grid',
            'sermon-browser/sermon-player',
            'sermon-browser/recent-sermons',
            'sermon-browser/popular-sermons',
            'sermon-browser/sermon-grid',
            'sermon-browser/profile-block',
            'sermon-browser/sermon-media',
            'sermon-browser/sermon-filters',
        ];

        // Ensure our blocks are always allowed.
        if (is_array($allowed_blocks)) {
            return array_unique(array_merge($allowed_blocks, $sermon_blocks));
        }

        return $allowed_blocks;
    }

    /**
     * Add block templates to the available templates list.
     *
     * @param array  $query_result Array of found block templates.
     * @param array  $query        Arguments to retrieve templates.
     * @param string $template_type Template type: 'wp_template' or 'wp_template_part'.
     * @return array Modified array of templates.
     */
    public function addBlockTemplates(array $query_result, array $query, string $template_type): array
    {
        // Only add templates for block themes.
        if (!wp_is_block_theme()) {
            return $query_result;
        }

        if ($template_type === 'wp_template') {
            $query_result = $this->addPluginTemplates($query_result, $query);
        } elseif ($template_type === 'wp_template_part') {
            $query_result = $this->addPluginTemplateParts($query_result, $query);
        }

        return $query_result;
    }

    /**
     * Add plugin block templates.
     *
     * @param array $query_result Existing templates.
     * @param array $query        Query parameters.
     * @return array Modified templates array.
     */
    private function addPluginTemplates(array $query_result, array $query): array
    {
        $template_files = [
            'archive-sermon' => [
                'title'       => __('Sermon Archive', 'sermon-browser'),
                'description' => __('Displays the sermon archive with filters and list.', 'sermon-browser'),
            ],
        ];

        foreach ($template_files as $slug => $info) {
            // Skip if already exists or slug filter doesn't match.
            if (isset($query['slug__in']) && !in_array($slug, $query['slug__in'], true)) {
                continue;
            }

            $template_file = $this->templatesPath . '/' . $slug . '.html';
            if (!file_exists($template_file)) {
                continue;
            }

            // Check if template already exists in results.
            $exists = false;
            foreach ($query_result as $template) {
                if ($template->slug === $slug) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $template = $this->createBlockTemplate($slug, $template_file, $info, 'wp_template');
                if ($template) {
                    $query_result[] = $template;
                }
            }
        }

        return $query_result;
    }

    /**
     * Add plugin block template parts.
     *
     * @param array $query_result Existing template parts.
     * @param array $query        Query parameters.
     * @return array Modified template parts array.
     */
    private function addPluginTemplateParts(array $query_result, array $query): array
    {
        $part_files = [
            'sermon-archive' => [
                'title'       => __('Sermon Archive', 'sermon-browser'),
                'description' => __('A sermon archive section with filters and list.', 'sermon-browser'),
                'area'        => 'sermon-browser',
            ],
            'single-sermon' => [
                'title'       => __('Single Sermon', 'sermon-browser'),
                'description' => __('A single sermon display with all details.', 'sermon-browser'),
                'area'        => 'sermon-browser',
            ],
        ];

        foreach ($part_files as $slug => $info) {
            // Skip if slug filter doesn't match.
            if (isset($query['slug__in']) && !in_array($slug, $query['slug__in'], true)) {
                continue;
            }

            // Skip if area filter doesn't match.
            if (isset($query['area']) && $query['area'] !== $info['area']) {
                continue;
            }

            $part_file = $this->partsPath . '/' . $slug . '.html';
            if (!file_exists($part_file)) {
                continue;
            }

            // Check if part already exists in results.
            $exists = false;
            foreach ($query_result as $part) {
                if ($part->slug === $slug && $part->theme === 'sermon-browser') {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $template = $this->createBlockTemplate($slug, $part_file, $info, 'wp_template_part');
                if ($template) {
                    $query_result[] = $template;
                }
            }
        }

        return $query_result;
    }

    /**
     * Create a block template object.
     *
     * @param string $slug          Template slug.
     * @param string $file_path     Path to the template file.
     * @param array  $info          Template info (title, description, area).
     * @param string $template_type Template type.
     * @return \WP_Block_Template|null The template object or null.
     */
    private function createBlockTemplate(
        string $slug,
        string $file_path,
        array $info,
        string $template_type
    ): ?\WP_Block_Template {
        $content = file_get_contents($file_path);
        if ($content === false) {
            return null;
        }

        $template = new \WP_Block_Template();
        $template->id = 'sermon-browser//' . $slug;
        $template->theme = 'sermon-browser';
        $template->slug = $slug;
        $template->source = 'plugin';
        $template->type = $template_type;
        $template->title = $info['title'];
        $template->description = $info['description'] ?? '';
        $template->status = 'publish';
        $template->has_theme_file = true;
        $template->origin = 'plugin';
        $template->is_custom = false;
        $template->content = $content;

        if ($template_type === 'wp_template_part' && isset($info['area'])) {
            $template->area = $info['area'];
        }

        return $template;
    }

    /**
     * Get block file template for specific slugs.
     *
     * @param \WP_Block_Template|null $block_template Block template.
     * @param string                  $id             Template ID.
     * @param string                  $template_type  Template type.
     * @return \WP_Block_Template|null The template or null.
     */
    public function getBlockFileTemplate($block_template, string $id, string $template_type)
    {
        // Check if this is our template.
        if (strpos($id, 'sermon-browser//') !== 0) {
            return $block_template;
        }

        $slug = str_replace('sermon-browser//', '', $id);

        if ($template_type === 'wp_template') {
            $file_path = $this->templatesPath . '/' . $slug . '.html';
            $info = $this->getTemplateInfo($slug);
        } else {
            $file_path = $this->partsPath . '/' . $slug . '.html';
            $info = $this->getTemplatePartInfo($slug);
        }

        if (!$info || !file_exists($file_path)) {
            return $block_template;
        }

        return $this->createBlockTemplate($slug, $file_path, $info, $template_type);
    }

    /**
     * Get template info by slug.
     *
     * @param string $slug Template slug.
     * @return array|null Template info or null.
     */
    private function getTemplateInfo(string $slug): ?array
    {
        $templates = [
            'archive-sermon' => [
                'title'       => __('Sermon Archive', 'sermon-browser'),
                'description' => __('Displays the sermon archive with filters and list.', 'sermon-browser'),
            ],
        ];

        return $templates[$slug] ?? null;
    }

    /**
     * Get template part info by slug.
     *
     * @param string $slug Template part slug.
     * @return array|null Template part info or null.
     */
    private function getTemplatePartInfo(string $slug): ?array
    {
        $parts = [
            'sermon-archive' => [
                'title'       => __('Sermon Archive', 'sermon-browser'),
                'description' => __('A sermon archive section with filters and list.', 'sermon-browser'),
                'area'        => 'sermon-browser',
            ],
            'single-sermon' => [
                'title'       => __('Single Sermon', 'sermon-browser'),
                'description' => __('A single sermon display with all details.', 'sermon-browser'),
                'area'        => 'sermon-browser',
            ],
        ];

        return $parts[$slug] ?? null;
    }

    /**
     * Get custom CSS variables from theme.json settings.
     *
     * @return string CSS custom properties.
     */
    public static function getCustomCssVariables(): string
    {
        return <<<CSS
:root {
    --sb-primary-color: var(--wp--custom--sermon-browser--primary-color, #0073aa);
    --sb-accent-color: var(--wp--custom--sermon-browser--accent-color, #005a87);
    --sb-card-radius: var(--wp--custom--sermon-browser--card-radius, 8px);
    --sb-card-shadow: var(--wp--custom--sermon-browser--card-shadow, 0 2px 4px rgba(0, 0, 0, 0.1));
    --sb-grid-gap: var(--wp--custom--sermon-browser--grid-gap, 1.5rem);
}
CSS;
    }

    /**
     * Reset the instance (primarily for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
