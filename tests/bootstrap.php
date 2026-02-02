<?php

/**
 * PHPUnit bootstrap file for Sermon Browser tests.
 *
 * Uses Brain Monkey and Mockery to mock WordPress functions,
 * avoiding the need for a full WordPress installation during testing.
 *
 * @package SermonBrowser\Tests
 */

declare(strict_types=1);

// Composer autoloader.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Define WordPress constants that the plugin expects.
if (!defined('ABSPATH')) {
    define('ABSPATH', '/var/www/html/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}

// Plugin-specific constants.
if (!defined('SB_CURRENT_VERSION')) {
    define('SB_CURRENT_VERSION', '0.5.1-dev');
}

if (!defined('SB_PLUGIN_URL')) {
    define('SB_PLUGIN_URL', 'http://localhost/wp-content/plugins/sermon-browser');
}

if (!defined('SB_PLUGIN_DIR')) {
    define('SB_PLUGIN_DIR', dirname(__DIR__));
}

// Note: SB_INCLUDES_DIR removed - sb-includes/ directory deleted in Phase 7.

if (!defined('SB_ABSPATH')) {
    define('SB_ABSPATH', '/var/www/html/');
}

if (!defined('IS_MU')) {
    define('IS_MU', false);
}

/**
 * WordPress REST API class stubs for testing.
 *
 * These are minimal implementations that allow our tests to run
 * without requiring WordPress to be loaded.
 */

if (!class_exists('WP_Error')) {
    /**
     * Stub for WordPress WP_Error class.
     */
    class WP_Error
    {
        private string $code;
        private string $message;
        private array $data;

        public function __construct(string $code = '', string $message = '', mixed $data = '')
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = is_array($data) ? $data : ['data' => $data];
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_data(): array
        {
            return $this->data;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    /**
     * Stub for WordPress WP_REST_Response class.
     */
    class WP_REST_Response
    {
        private mixed $data;
        private int $status;
        private array $headers = [];

        public function __construct(mixed $data = null, int $status = 200, array $headers = [])
        {
            $this->data = $data;
            $this->status = $status;
            $this->headers = $headers;
        }

        public function get_data(): mixed
        {
            return $this->data;
        }

        public function set_data(mixed $data): void
        {
            $this->data = $data;
        }

        public function get_status(): int
        {
            return $this->status;
        }

        public function set_status(int $status): void
        {
            $this->status = $status;
        }

        public function get_headers(): array
        {
            return $this->headers;
        }

        public function header(string $key, string $value, bool $replace = true): void
        {
            $this->headers[$key] = $value;
        }

        public function set_headers(array $headers): void
        {
            $this->headers = $headers;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    /**
     * Stub for WordPress WP_REST_Request class.
     */
    class WP_REST_Request
    {
        private string $method;
        private string $route;
        private array $params = [];
        private array $headers = [];

        public function __construct(string $method = 'GET', string $route = '', array $attributes = [])
        {
            $this->method = $method;
            $this->route = $route;
            $this->params = $attributes;
        }

        public function get_method(): string
        {
            return $this->method;
        }

        public function get_route(): string
        {
            return $this->route;
        }

        public function get_param(string $key): mixed
        {
            return $this->params[$key] ?? null;
        }

        public function set_param(string $key, mixed $value): void
        {
            $this->params[$key] = $value;
        }

        public function get_params(): array
        {
            return $this->params;
        }

        public function get_header(string $key): ?string
        {
            return $this->headers[$key] ?? null;
        }

        public function set_header(string $key, string $value): void
        {
            $this->headers[$key] = $value;
        }
    }
}

if (!class_exists('WP_REST_Controller')) {
    /**
     * Stub for WordPress WP_REST_Controller base class.
     *
     * Note: Properties are untyped to match WordPress 6.4 which doesn't use
     * typed properties on WP_REST_Controller.
     */
    abstract class WP_REST_Controller
    {
        protected $namespace = '';
        protected $rest_base = '';

        public function register_routes(): void
        {
            // To be implemented by child classes.
        }

        public function get_collection_params(): array
        {
            return [];
        }

        public function get_items_permissions_check($request): bool|WP_Error
        {
            return true;
        }

        public function get_item_permissions_check($request): bool|WP_Error
        {
            return true;
        }

        public function create_item_permissions_check($request): bool|WP_Error
        {
            return true;
        }

        public function update_item_permissions_check($request): bool|WP_Error
        {
            return true;
        }

        public function delete_item_permissions_check($request): bool|WP_Error
        {
            return true;
        }
    }
}

if (!class_exists('WP_Widget')) {
    /**
     * Stub for WordPress WP_Widget base class.
     */
    class WP_Widget
    {
        public string $id_base = '';
        public string $name = '';
        public int $number = 1;
        public array $widget_options = [];
        public array $control_options = [];

        public function __construct(
            string $id_base = '',
            string $name = '',
            array $widget_options = [],
            array $control_options = []
        ) {
            $this->id_base = $id_base;
            $this->name = $name;
            $this->widget_options = $widget_options;
            $this->control_options = $control_options;
        }

        public function widget($args, $instance): void
        {
            // To be implemented by child classes.
        }

        public function form($instance): void
        {
            // To be implemented by child classes.
        }

        public function update($new_instance, $old_instance): array
        {
            return $new_instance;
        }

        public function get_field_id(string $field_name): string
        {
            return 'widget-' . $this->id_base . '-' . $this->number . '-' . $field_name;
        }

        public function get_field_name(string $field_name): string
        {
            return 'widget-' . $this->id_base . '[' . $this->number . '][' . $field_name . ']';
        }
    }
}
