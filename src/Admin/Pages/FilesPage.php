<?php

/**
 * Files Page.
 *
 * Handles the Files admin page for managing uploaded sermon files.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

use SermonBrowser\Admin\Services\FileActionHandler;
use SermonBrowser\Constants;
use SermonBrowser\Facades\File;

/**
 * Class FilesPage
 *
 * Manages file uploads, linking, renaming, and deletion.
 */
class FilesPage
{
    /**
     * File types array.
     *
     * @var array
     */
    private array $filetypes;

    /**
     * File action handler service.
     *
     * @var FileActionHandler
     */
    private FileActionHandler $actionHandler;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $filetypes;
        $this->filetypes = $filetypes ?? [];
        $this->actionHandler = new FileActionHandler();
    }

    /**
     * Render the files page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!current_user_can('upload_files')) {
            wp_die(__("You do not have the correct permissions to upload sermons", 'sermon-browser'));
        }

        // Sync directory.
        sb_scan_dir();

        // Handle form submissions.
        $this->handlePost();

        // Load file data.
        $data = $this->loadFileData();

        // Render the page.
        $this->renderPage($data);
    }

    /**
     * Handle POST submissions.
     *
     * @return void
     */
    private function handlePost(): void
    {
        if (isset($_POST['import_url'])) {
            $this->actionHandler->handleUrlImport();
        } elseif (isset($_POST['save'])) {
            $this->actionHandler->handleFileUpload();
        } elseif (isset($_POST['clean'])) {
            $this->actionHandler->handleCleanup();
        }
    }

    /**
     * Load file data for display.
     *
     * @return array File data.
     */
    private function loadFileData(): array
    {
        $unlinked = File::findUnlinkedWithTitle(10);
        $linked = File::findLinkedWithTitle(10);
        $cntu = File::countUnlinked();
        $cntl = File::countLinked();

        return [
            'unlinked' => $unlinked,
            'linked' => $linked,
            'cntu' => $cntu,
            'cntl' => $cntl,
        ];
    }

    /**
     * Render the page HTML.
     *
     * @param array $data File data.
     * @return void
     */
    private function renderPage(array $data): void
    {
        sb_do_alerts();
        $this->renderPageScripts();
        $this->renderUploadSection();
        $this->renderUnlinkedFilesSection($data['unlinked']);
        $this->renderLinkedFilesSection($data['linked']);
        $this->renderSearchSection();
        $this->renderPaginationScripts($data['cntu'], $data['cntl']);
        $this->renderCleanupSection();
    }

    /**
     * Render the page JavaScript functions.
     *
     * @return void
     */
    private function renderPageScripts(): void
    {
        ?>
        <script>
            function rename(id, old) {
                var f = prompt("<?php _e('New file name?', 'sermon-browser') ?>", old);
                if (f != null) {
                    SBAdmin.file.rename(id, f, old).done(function(response) {
                        SBAdmin.handleResponse(response, function(data) {
                            jQuery('#' + id).text(f.substring(0,f.lastIndexOf(".")));
                            jQuery('#link' + id).attr('href', 'javascript:rename(' + id + ', "' + data.name + '")');
                            jQuery('#s' + id).text(f.substring(0,f.lastIndexOf(".")));
                            jQuery('#slink' + id).attr('href', 'javascript:rename(' + id + ', "' + data.name + '")');
                        }, function(message) {
                            alert(message || '<?php _e('The script is unable to rename your file.', 'sermon-browser') ?>');
                        });
                    });
                }
            }
            function kill(id, f) {
                SBAdmin.file.delete(id, f).done(function(response) {
                    SBAdmin.handleResponse(response, function() {
                        jQuery('#file' + id).fadeOut(function() {
                            jQuery('.file:visible').each(function(i) {
                                jQuery(this).removeClass('alternate');
                                if (++i % 2 == 0) {
                                    jQuery(this).addClass('alternate');
                                }
                            });
                        });
                        jQuery('#sfile' + id).fadeOut(function() {
                            jQuery('.file:visible').each(function(i) {
                                jQuery(this).removeClass('alternate');
                                if (++i % 2 == 0) {
                                    jQuery(this).addClass('alternate');
                                }
                            });
                        });
                    }, function(message) {
                        alert(message || '<?php _e('The script is unable to delete your file.', 'sermon-browser') ?>');
                    });
                });
            }
            var currentUnlinkedPage = 1;
            var currentLinkedPage = 1;
            function fetchU(page) {
                if (typeof page === 'undefined') page = 1;
                SBAdmin.filePagination.unlinked(page).done(function(response) {
                    SBAdmin.handleResponse(response, function(data) {
                        currentUnlinkedPage = data.page;
                        if (data.items.length > 0) {
                            jQuery('#the-list-u').html(SBAdmin.filePagination.renderRows(data.items));
                        } else {
                            jQuery('#the-list-u').html(SBAdmin.filePagination.renderNoResults());
                        }
                        jQuery('#uleft').html(data.has_prev ? '<a href="javascript:fetchU(' + (data.page - 1) + ')">' + SBAdmin.i18n.previous + '</a>' : '');
                        jQuery('#uright').html(data.has_next ? '<a href="javascript:fetchU(' + (data.page + 1) + ')">' + SBAdmin.i18n.next + '</a>' : '');
                    });
                });
            }
            function fetchL(page) {
                if (typeof page === 'undefined') page = 1;
                SBAdmin.filePagination.linked(page).done(function(response) {
                    SBAdmin.handleResponse(response, function(data) {
                        currentLinkedPage = data.page;
                        if (data.items.length > 0) {
                            jQuery('#the-list-l').html(SBAdmin.filePagination.renderRows(data.items));
                        } else {
                            jQuery('#the-list-l').html(SBAdmin.filePagination.renderNoResults());
                        }
                        jQuery('#left').html(data.has_prev ? '<a href="javascript:fetchL(' + (data.page - 1) + ')">' + SBAdmin.i18n.previous + '</a>' : '');
                        jQuery('#right').html(data.has_next ? '<a href="javascript:fetchL(' + (data.page + 1) + ')">' + SBAdmin.i18n.next + '</a>' : '');
                    });
                });
            }
            function findNow() {
                var searchTerm = jQuery('#search-input').val();
                if (!searchTerm) return;
                SBAdmin.filePagination.search(searchTerm).done(function(response) {
                    SBAdmin.handleResponse(response, function(data) {
                        if (data.items.length > 0) {
                            jQuery('#the-list-s').html(SBAdmin.filePagination.renderRows(data.items));
                        } else {
                            jQuery('#the-list-s').html(SBAdmin.filePagination.renderNoResults());
                        }
                    });
                });
            }
        </script>
        <?php
    }

    /**
     * Render the upload section.
     *
     * @return void
     */
    private function renderUploadSection(): void
    {
        ?>
        <div class="wrap" id="top">
            <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png" width="191" height="35" style="margin: 1em 2em; float: right;" alt="<?php esc_attr_e('Sermon Browser logo', 'sermon-browser'); ?>" /></a>
            <h2><?php _e('Upload Files', 'sermon-browser') ?></h2>
            <?php $this->renderImportOptionsWarning(); ?>
            <br style="clear:both">
            <?php sb_print_upload_form(); ?>
        </div>
        <?php
    }

    /**
     * Render import options warning if not set.
     *
     * @return void
     */
    private function renderImportOptionsWarning(): void
    {
        if (sb_import_options_set()) {
            return;
        }
        echo '<p class="plugin-update">';
        sb_print_import_options_message();
        echo "</p>\n";
    }

    /**
     * Render the unlinked files section.
     *
     * @param array<object> $unlinked Unlinked files.
     * @return void
     */
    private function renderUnlinkedFilesSection(array $unlinked): void
    {
        ?>
        <div class="wrap">
            <h2><?php _e('Unlinked files', 'sermon-browser') ?></h2>
            <br style="clear:both">
            <table class="widefat">
                <thead>
                    <tr>
                        <th style="width:10%" scope="col"><div style="text-align:center"><?php _e('ID', 'sermon-browser') ?></div></th>
                        <th style="width:50%" scope="col"><div style="text-align:center"><?php _e(Constants::LABEL_FILE_NAME, 'sermon-browser') ?></div></th>
                        <th style="width:20%" scope="col"><div style="text-align:center"><?php _e(Constants::LABEL_FILE_TYPE, 'sermon-browser') ?></div></th>
                        <th style="width:20%" scope="col"><div style="text-align:center"><?php _e('Actions', 'sermon-browser') ?></div></th>
                    </tr>
                </thead>
                <tbody id="the-list-u">
                    <?php $this->renderUnlinkedFileRows($unlinked); ?>
                </tbody>
            </table>
            <br style="clear:both">
            <div class="navigation">
                <div class="alignleft" id="uleft"></div>
                <div class="alignright" id="uright"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render unlinked file rows.
     *
     * @param array<object> $files Files to render.
     * @return void
     */
    private function renderUnlinkedFileRows(array $files): void
    {
        $i = 0;
        foreach ($files as $file) {
            $alternateClass = (++$i % 2 == 0) ? 'alternate' : '';
            $fileType = $this->getFileTypeName($file->name);
            ?>
            <tr class="file <?php echo $alternateClass ?>" id="u-file-<?php echo $file->id ?>">
                <th style="text-align:center" scope="row"><?php echo $file->id ?></th>
                <td id="u-name-<?php echo $file->id ?>"><?php echo $this->getFileBasename($file->name) ?></td>
                <td style="text-align:center"><?php echo $fileType ?></td>
                <td style="text-align:center">
                    <a href="<?php echo admin_url("admin.php?page=sermon-browser/new_sermon.php&amp;getid3={$file->id}"); ?>"><?php _e('Create sermon', 'sermon-browser') ?></a> |
                    <button type="button" id="u-link-<?php echo $file->id; ?>" class="button-link" onclick="rename(<?php echo $file->id; ?>, '<?php echo $file->name; ?>')"><?php _e('Rename', 'sermon-browser'); ?></button> | <button type="button" class="button-link" onclick="if(confirm('Do you really want to delete <?php echo str_replace("'", '', $file->name); ?>?')) kill(<?php echo $file->id; ?>, '<?php echo $file->name; ?>')"><?php _e('Delete', 'sermon-browser'); ?></button>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Render the linked files section.
     *
     * @param array<object> $linked Linked files.
     * @return void
     */
    private function renderLinkedFilesSection(array $linked): void
    {
        ?>
        <div class="wrap" id="linked">
            <h2><?php _e('Linked files', 'sermon-browser') ?></h2>
            <br style="clear:both">
            <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col"><div style="text-align:center"><?php _e('ID', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e(Constants::LABEL_FILE_NAME, 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e(Constants::LABEL_FILE_TYPE, 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('Sermon', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('Actions', 'sermon-browser') ?></div></th>
                    </tr>
                </thead>
                <tbody id="the-list-l">
                    <?php $this->renderLinkedFileRows($linked); ?>
                </tbody>
            </table>
            <br style="clear:both">
            <div class="navigation">
                <div class="alignleft" id="left"></div>
                <div class="alignright" id="right"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Render linked file rows.
     *
     * @param array<object> $files Files to render.
     * @return void
     */
    private function renderLinkedFileRows(array $files): void
    {
        $i = 0;
        foreach ($files as $file) {
            $alternateClass = (++$i % 2 == 0) ? 'alternate' : '';
            $fileType = $this->getFileTypeName($file->name);
            $safeName = str_replace("'", '', $file->name);
            $safeTitle = str_replace("'", '', $file->title);
            ?>
            <tr class="file <?php echo $alternateClass ?>" id="l-file-<?php echo $file->id ?>">
                <th style="text-align:center" scope="row"><?php echo $file->id ?></th>
                <td id="l-name-<?php echo $file->id ?>"><?php echo $this->getFileBasename($file->name) ?></td>
                <td style="text-align:center"><?php echo $fileType ?></td>
                <td><?php echo stripslashes($file->title) ?></td>
                <td style="text-align:center">
                    <script type="text/javascript">
                    function deletelinked_<?php echo $file->id;?>(filename, filesermon) {
                        if (confirm('Do you really want to delete '+filename+'?')) {
                            return confirm('This file is linked to the sermon called ['+filesermon+']. Are you sure you want to delete it?');
                        }
                        return false;
                    }
                    </script>
                    <button type="button" id="l-link-<?php echo $file->id; ?>" class="button-link" onclick="rename(<?php echo $file->id; ?>, '<?php echo $file->name ?>')"><?php _e('Rename', 'sermon-browser') ?></button> | <button type="button" class="button-link" onclick="if(deletelinked_<?php echo $file->id;?>('<?php echo $safeName; ?>', '<?php echo $safeTitle; ?>')) kill(<?php echo $file->id; ?>, '<?php echo $file->name; ?>')"><?php _e('Delete', 'sermon-browser'); ?></button>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Render the search section.
     *
     * @return void
     */
    private function renderSearchSection(): void
    {
        ?>
        <div class="wrap" id="search">
            <h2><?php _e('Search for files', 'sermon-browser') ?></h2>
            <form id="searchform" name="searchform">
                <p>
                    <input type="text" size="30" value="" id="search-input" />
                    <input type="submit" class="button" value="<?php _e('Search', 'sermon-browser') ?> &raquo;" onclick="javascript:findNow();return false;" />
                </p>
            </form>
            <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col"><div style="text-align:center"><?php _e('ID', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e(Constants::LABEL_FILE_NAME, 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e(Constants::LABEL_FILE_TYPE, 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('Sermon', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('Actions', 'sermon-browser') ?></div></th>
                    </tr>
                </thead>
                <tbody id="the-list-s">
                    <tr>
                        <td><?php _e('Search results will appear here.', 'sermon-browser') ?></td>
                    </tr>
                </tbody>
            </table>
            <br style="clear:both">
        </div>
        <?php
    }

    /**
     * Render pagination scripts.
     *
     * @param int $cntu Unlinked count.
     * @param int $cntl Linked count.
     * @return void
     */
    private function renderPaginationScripts(int $cntu, int $cntl): void
    {
        $sermonsPerPage = sb_get_option('sermons_per_page');
        ?>
        <script>
            <?php if ($cntu > $sermonsPerPage) : ?>
                jQuery('#uright').html('<a href="javascript:fetchU(2)"><?php _e('Next', 'sermon-browser') ?> &raquo;</a>');
            <?php endif ?>
            <?php if ($cntl > $sermonsPerPage) : ?>
                jQuery('#right').html('<a href="javascript:fetchL(2)"><?php _e('Next', 'sermon-browser') ?> &raquo;</a>');
            <?php endif ?>
        </script>
        <?php
    }

    /**
     * Render the cleanup section.
     *
     * @return void
     */
    private function renderCleanupSection(): void
    {
        global $checkSermonUpload;

        if (!isset($checkSermonUpload) || $checkSermonUpload !== 'writeable') {
            return;
        }
        ?>
        <div class="wrap">
            <h2><?php _e('Clean up', 'sermon-browser') ?></h2>
            <form method="post">
                <p><?php _e('Pressing the button below scans every sermon in the database, and removes missing attachments. Use with caution!', 'sermon-browser') ?></p>
                <input type="submit" name="clean" value="<?php _e('Clean up missing files', 'sermon-browser') ?>" />
                <?php wp_nonce_field('sermon_browser_clean', 'sermon_browser_clean_nonce'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get the file type display name.
     *
     * @param string $filename File name.
     * @return string Type name.
     */
    private function getFileTypeName(string $filename): string
    {
        $ext = substr($filename, strrpos($filename, '.') + 1);
        return $this->filetypes[$ext]['name'] ?? strtoupper($ext);
    }

    /**
     * Get file basename without extension.
     *
     * @param string $filename File name.
     * @return string Basename.
     */
    private function getFileBasename(string $filename): string
    {
        $pos = strrpos($filename, '.');
        return $pos !== false ? substr($filename, 0, $pos) : $filename;
    }
}
