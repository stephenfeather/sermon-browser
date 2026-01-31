<?php

/**
 * File Pagination AJAX Handler.
 *
 * Handles AJAX requests for file list pagination (unlinked, linked, search).
 *
 * @package SermonBrowser\Admin\Ajax
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Ajax;

use SermonBrowser\Facades\File;

/**
 * Class FilePaginationAjax
 *
 * AJAX handler for file list pagination.
 */
class FilePaginationAjax extends AjaxHandler
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
        add_action('wp_ajax_sb_file_unlinked', [$this, 'unlinked']);
        add_action('wp_ajax_sb_file_linked', [$this, 'linked']);
        add_action('wp_ajax_sb_file_search', [$this, 'search']);
    }

    /**
     * Get paginated unlinked files (not associated with any sermon).
     *
     * Expected POST data:
     * - page: int (required) - Current page number (1-based).
     * - per_page: int (optional) - Items per page (defaults to sermons_per_page option).
     *
     * @return void
     */
    public function unlinked(): void
    {
        $this->verifyRequest();

        $page = max(1, $this->getPostInt('page', 1));
        $perPage = $this->getPerPage();
        $offset = ($page - 1) * $perPage;

        $files = File::findUnlinkedWithTitle($perPage, $offset);
        $total = File::countUnlinked();

        $this->success([
            'items' => $this->formatFiles($files, true),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'has_prev' => $page > 1,
            'has_next' => ($offset + $perPage) < $total,
        ]);
    }

    /**
     * Get paginated linked files (associated with a sermon).
     *
     * Expected POST data:
     * - page: int (required) - Current page number (1-based).
     * - per_page: int (optional) - Items per page (defaults to sermons_per_page option).
     *
     * @return void
     */
    public function linked(): void
    {
        $this->verifyRequest();

        $page = max(1, $this->getPostInt('page', 1));
        $perPage = $this->getPerPage();
        $offset = ($page - 1) * $perPage;

        $files = File::findLinkedWithTitle($perPage, $offset);
        $total = File::countLinked();

        $this->success([
            'items' => $this->formatFiles($files, false),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'has_prev' => $page > 1,
            'has_next' => ($offset + $perPage) < $total,
        ]);
    }

    /**
     * Search files by name.
     *
     * Expected POST data:
     * - search: string (required) - Search term.
     * - page: int (optional) - Current page number (1-based).
     * - per_page: int (optional) - Items per page (defaults to sermons_per_page option).
     *
     * @return void
     */
    public function search(): void
    {
        $this->verifyRequest();

        $searchTerm = $this->getPostString('search');

        if (empty($searchTerm)) {
            $this->error(__('Search term is required.', 'sermon-browser'));
        }

        $page = max(1, $this->getPostInt('page', 1));
        $perPage = $this->getPerPage();
        $offset = ($page - 1) * $perPage;

        $files = File::searchByName($searchTerm, $perPage, $offset);
        $total = File::countBySearch($searchTerm);

        $this->success([
            'items' => $this->formatFiles($files, false),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            'has_prev' => $page > 1,
            'has_next' => ($offset + $perPage) < $total,
            'search' => $searchTerm,
        ]);
    }

    /**
     * Get items per page from POST or option.
     *
     * @return int Items per page.
     */
    private function getPerPage(): int
    {
        $perPage = $this->getPostInt('per_page', 0);

        if ($perPage <= 0) {
            $perPage = (int) sb_get_option('sermons_per_page', 10);
        }

        return $perPage;
    }

    /**
     * Format file data for JSON response.
     *
     * @param array<object> $files Array of file objects.
     * @param bool $isUnlinked Whether these are unlinked files.
     * @return array<array<string, mixed>> Formatted file data.
     */
    private function formatFiles(array $files, bool $isUnlinked): array
    {
        $filetypes = sb_get_option('filetypes');

        return array_map(function ($file) use ($isUnlinked, $filetypes) {
            $extension = pathinfo($file->name, PATHINFO_EXTENSION);
            $basename = pathinfo($file->name, PATHINFO_FILENAME);

            return [
                'id' => (int) $file->id,
                'name' => $file->name,
                'basename' => $basename,
                'extension' => $extension,
                'type_name' => $this->getTypeName($extension, $filetypes),
                'sermon_id' => (int) ($file->sermon_id ?? 0),
                'sermon_title' => stripslashes($file->title ?? ''),
                'is_unlinked' => $isUnlinked,
                'create_sermon_url' => $isUnlinked
                    ? admin_url("admin.php?page=sermon-browser/new_sermon.php&getid3={$file->id}")
                    : null,
            ];
        }, $files);
    }

    /**
     * Get human-readable type name for file extension.
     *
     * @param string $extension File extension.
     * @param array<string, array<string, string>> $filetypes Configured file types.
     * @return string Type name.
     */
    private function getTypeName(string $extension, array $filetypes): string
    {
        $ext = strtolower($extension);

        if (isset($filetypes[$ext]['name'])) {
            return $filetypes[$ext]['name'];
        }

        return strtoupper($ext);
    }
}
