<?php
/**
 * AJAX Registry.
 *
 * Registers all AJAX handlers with WordPress.
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

/**
 * Class AjaxRegistry
 *
 * Central registry for all AJAX handlers.
 */
class AjaxRegistry
{
    /**
     * Registered handlers.
     *
     * @var array<AjaxHandler>
     */
    private array $handlers = [];

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Private constructor for singleton pattern.
     */
    private function __construct()
    {
        $this->registerDefaultHandlers();
    }

    /**
     * Get the singleton instance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register the default AJAX handlers.
     *
     * @return void
     */
    private function registerDefaultHandlers(): void
    {
        $this->addHandler(new PreacherAjax());
        $this->addHandler(new SeriesAjax());
        $this->addHandler(new ServiceAjax());
        $this->addHandler(new FileAjax());
        $this->addHandler(new SermonPaginationAjax());
        $this->addHandler(new FilePaginationAjax());
    }

    /**
     * Add an AJAX handler to the registry.
     *
     * @param AjaxHandler $handler The handler to add.
     * @return self
     */
    public function addHandler(AjaxHandler $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    /**
     * Register all handlers with WordPress.
     *
     * This should be called on the 'init' hook or similar.
     *
     * @return void
     */
    public function register(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->register();
        }
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
