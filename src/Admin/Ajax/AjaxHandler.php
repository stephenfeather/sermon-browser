<?php

/**
 * AJAX Handler base class.
 *
 * Provides common functionality for all AJAX handlers including
 * nonce verification and capability checks.
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

/**
 * Class AjaxHandler
 *
 * Abstract base class for AJAX handlers.
 */
abstract class AjaxHandler
{
    /**
     * The nonce action name for this handler.
     *
     * @var string
     */
    protected string $nonceAction = 'sb_ajax_nonce';

    /**
     * The nonce field name in requests.
     *
     * @var string
     */
    protected string $nonceField = '_sb_nonce';

    /**
     * Required capability for this handler.
     *
     * @var string
     */
    protected string $capability = 'edit_posts';

    /**
     * Register the AJAX actions with WordPress.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Verify the nonce from the request.
     *
     * @return bool True if nonce is valid.
     */
    protected function verifyNonce(): bool
    {
        $nonce = '';

        if (isset($_POST[$this->nonceField])) {
            $nonce = sanitize_text_field(wp_unslash($_POST[$this->nonceField]));
        } elseif (isset($_GET[$this->nonceField])) {
            $nonce = sanitize_text_field(wp_unslash($_GET[$this->nonceField]));
        }

        return wp_verify_nonce($nonce, $this->nonceAction) !== false;
    }

    /**
     * Check if current user has required capability.
     *
     * @return bool True if user has capability.
     */
    protected function checkCapability(): bool
    {
        return current_user_can($this->capability);
    }

    /**
     * Verify request has valid nonce and user has capability.
     *
     * Sends error response and dies if verification fails.
     *
     * @return void
     */
    protected function verifyRequest(): void
    {
        if (!$this->verifyNonce()) {
            wp_send_json_error(
                ['message' => __('Security check failed.', 'sermon-browser')],
                403
            );
        }

        if (!$this->checkCapability()) {
            wp_send_json_error(
                ['message' => __('You do not have permission to perform this action.', 'sermon-browser')],
                403
            );
        }
    }

    /**
     * Get a sanitized string from POST data.
     *
     * @param string $key The POST key.
     * @param string $default Default value if key doesn't exist.
     * @return string The sanitized value.
     */
    protected function getPostString(string $key, string $default = ''): string
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        return sanitize_text_field(wp_unslash($_POST[$key]));
    }

    /**
     * Get a sanitized integer from POST data.
     *
     * @param string $key The POST key.
     * @param int $default Default value if key doesn't exist.
     * @return int The sanitized value.
     */
    protected function getPostInt(string $key, int $default = 0): int
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        return (int) $_POST[$key];
    }

    /**
     * Check if a POST key exists.
     *
     * @param string $key The POST key.
     * @return bool True if key exists.
     */
    protected function hasPost(string $key): bool
    {
        return isset($_POST[$key]);
    }

    /**
     * Send a success response.
     *
     * @param mixed $data Data to send.
     * @return void
     */
    protected function success(mixed $data = null): void
    {
        wp_send_json_success($data);
    }

    /**
     * Send an error response.
     *
     * @param string $message Error message.
     * @param int $statusCode HTTP status code.
     * @return void
     */
    protected function error(string $message, int $statusCode = 400): void
    {
        wp_send_json_error(['message' => $message], $statusCode);
    }
}
