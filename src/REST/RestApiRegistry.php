<?php

/**
 * REST API Registry.
 *
 * Registers all REST controllers with WordPress on rest_api_init.
 *
 * @package SermonBrowser\REST
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\REST;

/**
 * Class RestApiRegistry
 *
 * Central registry for all REST API controllers.
 * Hooks into WordPress rest_api_init to register routes.
 */
class RestApiRegistry
{
    /**
     * Registered controllers.
     *
     * @var array<RestController>
     */
    private array $controllers = [];

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
        // No default controllers registered.
        // Controllers are added via addController().
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
     * Add a REST controller to the registry.
     *
     * @param RestController $controller The controller to add.
     * @return self For method chaining.
     */
    public function addController(RestController $controller): self
    {
        $this->controllers[] = $controller;
        return $this;
    }

    /**
     * Get all registered controllers.
     *
     * @return array<RestController>
     */
    public function getControllers(): array
    {
        return $this->controllers;
    }

    /**
     * Initialize the REST API by hooking into WordPress.
     *
     * This should be called during plugin initialization.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'register']);
    }

    /**
     * Register all controllers with WordPress.
     *
     * Called on the 'rest_api_init' hook.
     *
     * @return void
     */
    public function register(): void
    {
        foreach ($this->controllers as $controller) {
            $controller->register_routes();
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
