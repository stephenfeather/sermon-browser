<?php

/**
 * Block Registry for Gutenberg blocks.
 *
 * Registers all Sermon Browser Gutenberg blocks with WordPress.
 *
 * @package SermonBrowser\Blocks
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Blocks;

/**
 * Class BlockRegistry
 *
 * Central registry for all Gutenberg blocks.
 * Uses register_block_type() with block.json for metadata.
 */
class BlockRegistry
{
    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Registered block names.
     *
     * @var array<string>
     */
    private array $blocks = [];

    /**
     * Script handle for editor blocks.
     */
    private const EDITOR_SCRIPT_HANDLE = 'sermon-browser-blocks-editor';

    /**
     * Style handle for frontend.
     */
    private const FRONTEND_STYLE_HANDLE = 'sermon-browser-blocks-style';

    /**
     * Private constructor for singleton pattern.
     */
    private function __construct()
    {
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
     * Add a block to the registry.
     *
     * @param string $name Block directory name (e.g., 'tag-cloud').
     * @return self For method chaining.
     */
    public function addBlock(string $name): self
    {
        $this->blocks[] = $name;
        return $this;
    }

    /**
     * Initialize block registration by hooking into WordPress.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('init', [$this, 'register']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueEditorAssets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
    }

    /**
     * Register all blocks with WordPress.
     *
     * Called on the 'init' hook.
     *
     * @return void
     */
    public function register(): void
    {
        foreach ($this->blocks as $block) {
            $path = SB_PLUGIN_DIR . '/sermon-browser/build/blocks/Blocks/' . $block;
            if (file_exists($path . '/block.json')) {
                register_block_type($path);
            }
        }
    }

    /**
     * Enqueue editor assets (JS and CSS for block editor).
     *
     * @return void
     */
    public function enqueueEditorAssets(): void
    {
        $build_path = SB_PLUGIN_DIR . '/sermon-browser/build/blocks';
        $build_url = SB_PLUGIN_URL . '/build/blocks';

        // Check if build files exist.
        if (!file_exists($build_path . '/index.js')) {
            return;
        }

        // Load asset dependencies from generated file.
        $asset_file = $build_path . '/index.asset.php';
        $asset = file_exists($asset_file)
            ? require $asset_file
            : ['dependencies' => [], 'version' => SB_CURRENT_VERSION];

        wp_enqueue_script(
            self::EDITOR_SCRIPT_HANDLE,
            $build_url . '/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        // Enqueue editor styles if they exist.
        if (file_exists($build_path . '/style-index.css')) {
            wp_enqueue_style(
                self::FRONTEND_STYLE_HANDLE,
                $build_url . '/style-index.css',
                [],
                $asset['version']
            );
        }
    }

    /**
     * Enqueue frontend assets (CSS for rendered blocks).
     *
     * @return void
     */
    public function enqueueFrontendAssets(): void
    {
        // Only enqueue if a block is used on the page.
        if (!$this->hasBlocksOnPage()) {
            return;
        }

        $build_path = SB_PLUGIN_DIR . '/sermon-browser/build/blocks';
        $build_url = SB_PLUGIN_URL . '/build/blocks';

        if (file_exists($build_path . '/style-index.css')) {
            $asset_file = $build_path . '/index.asset.php';
            $version = file_exists($asset_file)
                ? (require $asset_file)['version']
                : SB_CURRENT_VERSION;

            wp_enqueue_style(
                self::FRONTEND_STYLE_HANDLE,
                $build_url . '/style-index.css',
                [],
                $version
            );
        }
    }

    /**
     * Check if any sermon browser blocks are used on the current page.
     *
     * @return bool
     */
    private function hasBlocksOnPage(): bool
    {
        if (!is_singular()) {
            return false;
        }

        $post = get_post();
        if (!$post) {
            return false;
        }

        foreach ($this->blocks as $block) {
            if (has_block('sermon-browser/' . $block, $post)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all registered block names.
     *
     * @return array<string>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Reset the registry (primarily for testing).
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
