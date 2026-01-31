<?php

/**
 * File AJAX Handler.
 *
 * Handles AJAX requests for file operations (rename, delete).
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

use SermonBrowser\Facades\File;

/**
 * Class FileAjax
 *
 * AJAX handler for file operations.
 */
class FileAjax extends AjaxHandler
{
    /**
     * {@inheritDoc}
     */
    protected string $nonceAction = 'sb_file_nonce';

    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        add_action('wp_ajax_sb_file_rename', [$this, 'rename']);
        add_action('wp_ajax_sb_file_delete', [$this, 'delete']);
    }

    /**
     * Get the upload directory path.
     *
     * @return string The upload directory.
     */
    private function getUploadDir(): string
    {
        return sb_get_option('upload_dir');
    }

    /**
     * Get the absolute path to a file.
     *
     * @param string $filename The filename.
     * @return string The absolute file path.
     */
    private function getFilePath(string $filename): string
    {
        return SB_ABSPATH . $this->getUploadDir() . $filename;
    }

    /**
     * Validate that a filename is safe.
     *
     * @param string $filename The filename to validate.
     * @return bool True if valid.
     */
    private function isValidFilename(string $filename): bool
    {
        $path = $this->getUploadDir() . $filename;
        return validate_file($path) === 0;
    }

    /**
     * Check if file extension is allowed (for multisite).
     *
     * @param string $filename The filename to check.
     * @return bool True if allowed.
     */
    private function isFileExtensionAllowed(string $filename): bool
    {
        if (!defined('IS_MU') || !IS_MU) {
            return true;
        }

        $allowedExtensions = explode(' ', get_site_option('upload_filetypes', ''));

        foreach ($allowedExtensions as $ext) {
            $ext = strtolower(trim($ext));
            if (empty($ext)) {
                continue;
            }

            if (str_ends_with(strtolower($filename), '.' . $ext)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rename a file.
     *
     * Expected POST data:
     * - id: int (required) - The file ID.
     * - name: string (required) - The new filename.
     * - old_name: string (required) - The original filename.
     *
     * @return void
     */
    public function rename(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');
        $newName = sanitize_file_name($this->getPostString('name'));
        $oldName = sanitize_file_name($this->getPostString('old_name'));

        if ($id <= 0) {
            $this->error(__('Invalid file ID.', 'sermon-browser'));
        }

        if (empty($newName) || empty($oldName)) {
            $this->error(__('Filename is required.', 'sermon-browser'));
        }

        // Validate both filenames
        if (!$this->isValidFilename($newName) || !$this->isValidFilename($oldName)) {
            $this->error(__('Invalid filename.', 'sermon-browser'));
        }

        // Check file extension for multisite
        if (!$this->isFileExtensionAllowed($newName)) {
            $this->error(__('File type not allowed.', 'sermon-browser'));
        }

        $oldPath = $this->getFilePath($oldName);
        $newPath = $this->getFilePath($newName);

        // Check if destination already exists
        if (file_exists($newPath)) {
            $this->error(__('A file with that name already exists.', 'sermon-browser'));
        }

        // Rename the physical file
        if (!file_exists($oldPath) || !rename($oldPath, $newPath)) {
            $this->error(__('Failed to rename file.', 'sermon-browser'));
        }

        // Update the database record
        $result = File::update($id, ['name' => $newName]);

        if (!$result) {
            // Attempt to rollback the file rename
            @rename($newPath, $oldPath);
            $this->error(__('Failed to update file record.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'name' => $newName,
            'message' => __('File renamed successfully.', 'sermon-browser'),
        ]);
    }

    /**
     * Delete a file.
     *
     * Expected POST data:
     * - id: int (required) - The file ID.
     * - name: string (required) - The filename to delete.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->verifyRequest();

        $id = $this->getPostInt('id');
        $filename = sanitize_file_name($this->getPostString('name'));

        if ($id <= 0) {
            $this->error(__('Invalid file ID.', 'sermon-browser'));
        }

        if (empty($filename)) {
            $this->error(__('Filename is required.', 'sermon-browser'));
        }

        // Validate filename
        if (!$this->isValidFilename($filename)) {
            $this->error(__('Invalid filename.', 'sermon-browser'));
        }

        $filePath = $this->getFilePath($filename);

        // Delete the physical file (if it exists)
        if (file_exists($filePath) && !unlink($filePath)) {
            $this->error(__('Failed to delete file.', 'sermon-browser'));
        }

        // Delete the database record
        $result = File::delete($id);

        if (!$result) {
            $this->error(__('Failed to delete file record.', 'sermon-browser'));
        }

        $this->success([
            'id' => $id,
            'message' => __('File deleted successfully.', 'sermon-browser'),
        ]);
    }
}
