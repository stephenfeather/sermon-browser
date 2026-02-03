<?php

/**
 * File Action Handler Service.
 *
 * Handles file-related form submissions (URL import, upload, cleanup).
 *
 * @package SermonBrowser\Admin\Services
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Services;

use SermonBrowser\Constants;
use SermonBrowser\Facades\File;

/**
 * Class FileActionHandler
 *
 * Processes file action requests from the Files admin page.
 */
class FileActionHandler
{
    /**
     * Handle URL import.
     *
     * @return void
     */
    public function handleUrlImport(): void
    {
        // Security: Verify nonce to prevent CSRF attacks.
        if (
            !isset($_POST['sb_file_import_nonce']) ||
            !wp_verify_nonce($_POST['sb_file_import_nonce'], 'sb_file_import')
        ) {
            wp_die(
                esc_html__('Security check failed. Please refresh the page and try again.', 'sermon-browser'),
                esc_html__('Security Error', 'sermon-browser'),
                ['response' => 403]
            );
        }

        $url = esc_url($_POST['url']);

        // Validate URL scheme to prevent SSRF attacks.
        $parsed_url = wp_parse_url($url);
        if (!isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], ['http', 'https'], true)) {
            echo '<div id="message" class="updated fade"><p><b>' .
                esc_html__('Invalid URL scheme. Only HTTP and HTTPS are allowed.', 'sermon-browser') .
                '</b></div>';
            return;
        }

        // Use WordPress HTTP API for safe remote requests (protects against SSRF).
        $response = wp_safe_remote_head($url, [
            'timeout' => 10,
            'redirection' => 5,
        ]);

        if (is_wp_error($response)) {
            echo '<div id="message" class="updated fade"><p><b>' .
                esc_html__('Could not fetch URL: ', 'sermon-browser') . esc_html($response->get_error_message()) .
                '</b></div>';
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            echo '<div id="message" class="updated fade"><p><b>' .
                esc_html__('Invalid URL.', 'sermon-browser') . '</b></div>';
            return;
        }

        if ($_POST['import_type'] === 'download') {
            $this->downloadRemoteFile($url);
        } else {
            $fileId = File::create([
                'type' => 'url',
                'name' => $url,
                'sermon_id' => 0,
                'count' => 0,
                'ccount' => 0,
            ]);
            echo "<script>document.location = '" .
                admin_url(Constants::NEW_SERMON_GETID3 . $fileId) .
                "';</script>";
            die();
        }
    }

    /**
     * Download a remote file.
     *
     * @param string $url Remote URL.
     * @return void
     */
    private function downloadRemoteFile(string $url): void
    {
        $filename = substr($url, strrpos($url, '/') + 1);
        $filename = substr($filename, 0, strrpos($filename, '?') ?: strlen($filename));

        // Security: Sanitize filename to prevent path traversal attacks.
        // sanitize_file_name() removes path components, special characters, and normalizes the filename.
        $filename = sanitize_file_name($filename);

        if (file_exists(SB_ABSPATH . sb_get_option('upload_dir') . $filename)) {
            echo '<div id="message" class="updated fade"><p><b>' .
                sprintf(__('File %s already exists', 'sermon-browser'), $filename) .
                '</b></div>';
            return;
        }

        $file = @fopen(SB_ABSPATH . sb_get_option('upload_dir') . $filename, 'wb');
        $remote_file = @fopen($url, 'r');

        if ($file && $remote_file) {
            $remote_contents = '';
            while (!feof($remote_file)) {
                $remote_contents .= fread($remote_file, 8192);
            }
            fwrite($file, $remote_contents);
            fclose($remote_file);
            fclose($file);

            $fileId = File::create([
                'type' => 'file',
                'name' => $filename,
                'sermon_id' => 0,
                'count' => 0,
                'ccount' => 0,
            ]);

            echo "<script>document.location = '" .
                admin_url(Constants::NEW_SERMON_GETID3 . $fileId) .
                "';</script>";
        }
    }

    /**
     * Handle file upload.
     *
     * @return void
     */
    public function handleFileUpload(): void
    {
        // Security: Verify nonce to prevent CSRF attacks.
        if (
            !isset($_POST['sb_file_upload_nonce']) ||
            !wp_verify_nonce($_POST['sb_file_upload_nonce'], 'sb_file_upload')
        ) {
            wp_die(
                esc_html__('Security check failed. Please refresh the page and try again.', 'sermon-browser'),
                esc_html__('Security Error', 'sermon-browser'),
                ['response' => 403]
            );
        }

        if ($_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $filename = basename($_FILES['upload']['name']);

        // Check file type for multisite.
        if (IS_MU) {
            $file_allowed = false;
            require_once SB_ABSPATH . 'wp-includes/ms-functions.php'; // NOSONAR S4833 - WordPress core not namespaced
            $allowed_extensions = explode(" ", get_site_option("upload_filetypes"));
            foreach ($allowed_extensions as $ext) {
                if (substr(strtolower($filename), -(strlen($ext) + 1)) === "." . strtolower($ext)) {
                    $file_allowed = true;
                }
            }
        } else {
            $file_allowed = true;
        }

        if (!$file_allowed) {
            @unlink($_FILES['upload']['tmp_name']);
            echo '<div id="message" class="updated fade"><p><b>' .
                __('You are not permitted to upload files of that type.', 'sermon-browser') .
                '</b></div>';
            return;
        }

        $prefix = '';
        $dest = SB_ABSPATH . sb_get_option('upload_dir') . $prefix . $filename;

        if (File::existsByName($filename)) {
            echo '<div id="message" class="updated fade"><p><b>' .
                esc_html($filename) . ' ' . esc_html__('already exists.', 'sermon-browser') .
                '</b></div>';
            return;
        }

        if (move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
            $filename = $prefix . $filename;
            $fileId = File::create([
                'type' => 'file',
                'name' => $filename,
                'sermon_id' => 0,
                'count' => 0,
                'ccount' => 0,
            ]);

            if (sb_import_options_set()) {
                echo "<script>document.location = '" .
                    admin_url(Constants::NEW_SERMON_GETID3 . $fileId) .
                    "';</script>";
            } else {
                echo '<div id="message" class="updated fade"><p><b>' .
                    __('Files saved to database.', 'sermon-browser') .
                    '</b></div>';
            }
        }
    }

    /**
     * Handle cleanup of missing files.
     *
     * @return void
     */
    public function handleCleanup(): void
    {
        if (
            !isset($_POST['sermon_browser_clean_nonce']) ||
            !wp_verify_nonce($_POST['sermon_browser_clean_nonce'], 'sermon_browser_clean')
        ) {
            wp_die(__('Access denied.', 'sermon-browser'));
        }

        // Get all files (unlinked and linked).
        $unlinked = File::findUnlinkedWithTitle(0);
        $linked = File::findLinkedWithTitle(0);

        $wanted = [];

        foreach ((array) $unlinked as $file) {
            if (!file_exists(SB_ABSPATH . sb_get_option('upload_dir') . $file->name)) {
                $wanted[] = $file->id;
            }
        }

        foreach ((array) $linked as $file) {
            if (!file_exists(SB_ABSPATH . sb_get_option('upload_dir') . $file->name)) {
                $wanted[] = $file->id;
            }
        }

        if (!empty($wanted)) {
            File::deleteByIds($wanted);
        }
        File::deleteOrphanedNonFiles();
    }
}
