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

/**
 * Class FilesPage
 *
 * Manages file uploads, linking, renaming, and deletion.
 */
class FilesPage
{
    /**
     * WordPress database instance.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * File types array.
     *
     * @var array
     */
    private array $filetypes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $wpdb, $filetypes;
        $this->wpdb = $wpdb;
        $this->filetypes = $filetypes ?? [];
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
            $this->handleUrlImport();
        } elseif (isset($_POST['save'])) {
            $this->handleFileUpload();
        } elseif (isset($_POST['clean'])) {
            $this->handleCleanup();
        }
    }

    /**
     * Handle URL import.
     *
     * @return void
     */
    private function handleUrlImport(): void
    {
        $url = esc_url($_POST['url']);

        if (!ini_get('allow_url_fopen')) {
            echo '<div id="message" class="updated fade"><p><b>' .
                __('Your host does not allow remote downloading of files.', 'sermon-browser') .
                '</b></div>';
            return;
        }

        $headers = array_change_key_case(get_headers($url, 1), CASE_LOWER);
        $matches = [];
        $matched = preg_match('#HTTP/\d+\.\d+ (\d+)#', $headers[0], $matches);

        if (!$matched || $matches[1] !== '200') {
            echo '<div id="message" class="updated fade"><p><b>' .
                __('Invalid URL.', 'sermon-browser') . '</b></div>';
            return;
        }

        if ($_POST['import_type'] === 'download') {
            $this->downloadRemoteFile($url);
        } else {
            $this->wpdb->query($this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}sb_stuff VALUES (null, 'url', %s, 0, 0, 0)",
                $url
            ));
            echo "<script>document.location = '" .
                admin_url('admin.php?page=sermon-browser/new_sermon.php&getid3=' . $this->wpdb->insert_id) .
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

            $this->wpdb->query($this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}sb_stuff VALUES (null, 'file', %s, 0, 0, 0)",
                $filename
            ));

            echo "<script>document.location = '" .
                admin_url('admin.php?page=sermon-browser/new_sermon.php&getid3=' . $this->wpdb->insert_id) .
                "';</script>";
        }
    }

    /**
     * Handle file upload.
     *
     * @return void
     */
    private function handleFileUpload(): void
    {
        if ($_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $filename = basename($_FILES['upload']['name']);

        // Check file type for multisite.
        if (IS_MU) {
            $file_allowed = false;
            require_once(SB_ABSPATH . 'wp-includes/ms-functions.php');
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

        if ($this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sb_stuff WHERE name = %s",
            $filename
        )) != 0) {
            echo '<div id="message" class="updated fade"><p><b>' .
                __($filename . ' already exists.', 'sermon-browser') .
                '</b></div>';
            return;
        }

        if (move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
            $filename = $prefix . $filename;
            $this->wpdb->query($this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}sb_stuff VALUES (null, 'file', %s, 0, 0, 0)",
                $filename
            ));

            if (sb_import_options_set()) {
                echo "<script>document.location = '" .
                    admin_url('admin.php?page=sermon-browser/new_sermon.php&getid3=' . $this->wpdb->insert_id) .
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
    private function handleCleanup(): void
    {
        if (!isset($_POST['sermon_browser_clean_nonce']) ||
            !wp_verify_nonce($_POST['sermon_browser_clean_nonce'], 'sermon_browser_clean')) {
            wp_die(__('Access denied.', 'sermon-browser'));
        }

        $unlinked = $this->wpdb->get_results(
            "SELECT f.*, s.title FROM {$this->wpdb->prefix}sb_stuff AS f " .
            "LEFT JOIN {$this->wpdb->prefix}sb_sermons AS s ON f.sermon_id = s.id " .
            "WHERE f.sermon_id = 0 AND f.type = 'file' ORDER BY f.name;"
        );

        $linked = $this->wpdb->get_results(
            "SELECT f.*, s.title FROM {$this->wpdb->prefix}sb_stuff AS f " .
            "LEFT JOIN {$this->wpdb->prefix}sb_sermons AS s ON f.sermon_id = s.id " .
            "WHERE f.sermon_id <> 0 AND f.type = 'file' ORDER BY f.name;"
        );

        $wanted = [-1];

        foreach ((array) $unlinked as $k => $file) {
            if (!file_exists(SB_ABSPATH . sb_get_option('upload_dir') . $file->name)) {
                $wanted[] = $file->id;
            }
        }

        foreach ((array) $linked as $k => $file) {
            if (!file_exists(SB_ABSPATH . sb_get_option('upload_dir') . $file->name)) {
                $wanted[] = $file->id;
            }
        }

        $this->wpdb->query(
            "DELETE FROM {$this->wpdb->prefix}sb_stuff WHERE id IN (" .
            implode(', ', (array) $wanted) . ")"
        );
        $this->wpdb->query(
            "DELETE FROM {$this->wpdb->prefix}sb_stuff WHERE type != 'file' AND sermon_id=0"
        );
    }

    /**
     * Load file data for display.
     *
     * @return array File data.
     */
    private function loadFileData(): array
    {
        $unlinked = $this->wpdb->get_results(
            "SELECT f.*, s.title FROM {$this->wpdb->prefix}sb_stuff AS f " .
            "LEFT JOIN {$this->wpdb->prefix}sb_sermons AS s ON f.sermon_id = s.id " .
            "WHERE f.sermon_id = 0 AND f.type = 'file' ORDER BY f.name LIMIT 10;"
        );

        $linked = $this->wpdb->get_results(
            "SELECT f.*, s.title FROM {$this->wpdb->prefix}sb_stuff AS f " .
            "LEFT JOIN {$this->wpdb->prefix}sb_sermons AS s ON f.sermon_id = s.id " .
            "WHERE f.sermon_id <> 0 AND f.type = 'file' ORDER BY f.name LIMIT 10;"
        );

        $cntu = $this->wpdb->get_row(
            "SELECT COUNT(*) as cntu FROM {$this->wpdb->prefix}sb_stuff " .
            "WHERE sermon_id = 0 AND type = 'file'",
            ARRAY_A
        );

        $cntl = $this->wpdb->get_row(
            "SELECT COUNT(*) as cntl FROM {$this->wpdb->prefix}sb_stuff " .
            "WHERE sermon_id <> 0 AND type = 'file'",
            ARRAY_A
        );

        return [
            'unlinked' => $unlinked,
            'linked' => $linked,
            'cntu' => (int) ($cntu['cntu'] ?? 0),
            'cntl' => (int) ($cntl['cntl'] ?? 0),
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
        global $checkSermonUpload;

        $unlinked = $data['unlinked'];
        $linked = $data['linked'];
        $cntu = $data['cntu'];
        $cntl = $data['cntl'];
        $filetypes = $this->filetypes;
        $sermonsPerPage = sb_get_option('sermons_per_page');

        sb_do_alerts();
        ?>
        <script>
            function rename(id, old) {
                var f = prompt("<?php _e('New file name?', 'sermon-browser') ?>", old);
                if (f != null) {
                    jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/uploads.php'); ?>', {fid: id, oname: old, fname: f, sermon: 1}, function(r) {
                        if (r) {
                            if (r == 'renamed') {
                                jQuery('#' + id).text(f.substring(0,f.lastIndexOf(".")));
                                jQuery('#link' + id).attr('href', 'javascript:rename(' + id + ', "' + f + '")');
                                jQuery('#s' + id).text(f.substring(0,f.lastIndexOf(".")));
                                jQuery('#slink' + id).attr('href', 'javascript:rename(' + id + ', "' + f + '")');
                            } else {
                                if (r == 'forbidden') {
                                    alert('<?php _e('You are not permitted files with that extension.', 'sermon-browser') ?>');
                                } else {
                                    alert('<?php _e('The script is unable to rename your file.', 'sermon-browser') ?>');
                                }
                            }
                        };
                    });
                }
            }
            function kill(id, f) {
                jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/files.php'); ?>', {fname: f, fid: id, del: 1, sermon: 1}, function(r) {
                    if (r) {
                        if (r == 'deleted') {
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
                        } else {
                            alert('<?php _e('The script is unable to delete your file.', 'sermon-browser') ?>');
                        }
                    };
                });
            }
            function fetchU(st) {
                jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/uploads.php'); ?>', {fetchU: st + 1, sermon: 1}, function(r) {
                    if (r) {
                        jQuery('#the-list-u').html(r);
                        if (st >= <?php echo $sermonsPerPage ?>) {
                            x = st - <?php echo $sermonsPerPage ?>;
                            jQuery('#uleft').html('<a href="javascript:fetchU(' + x + ')">&laquo; <?php _e('Previous', 'sermon-browser') ?></a>');
                        } else {
                            jQuery('#uleft').html('');
                        }
                        if (st + <?php echo $sermonsPerPage ?> <= <?php echo $cntu ?>) {
                            y = st + <?php echo $sermonsPerPage ?>;
                            jQuery('#uright').html('<a href="javascript:fetchU(' + y + ')"><?php _e('Next', 'sermon-browser') ?> &raquo;</a>');
                        } else {
                            jQuery('#uright').html('');
                        }
                    };
                });
            }
            function fetchL(st) {
                jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/files.php'); ?>', {fetchL: st + 1, sermon: 1}, function(r) {
                    if (r) {
                        jQuery('#the-list-l').html(r);
                        if (st >= <?php echo $sermonsPerPage ?>) {
                            x = st - <?php echo $sermonsPerPage ?>;
                            jQuery('#left').html('<a href="javascript:fetchL(' + x + ')">&laquo; <?php _e('Previous', 'sermon-browser') ?></a>');
                        } else {
                            jQuery('#left').html('');
                        }
                        if (st + <?php echo $sermonsPerPage ?> <= <?php echo $cntl ?>) {
                            y = st + <?php echo $sermonsPerPage ?>;
                            jQuery('#right').html('<a href="javascript:fetchL(' + y + ')"><?php _e('Next', 'sermon-browser') ?> &raquo;</a>');
                        } else {
                            jQuery('#right').html('');
                        }
                    };
                });
            }
            function findNow() {
                jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/files.php'); ?>', {search: jQuery('#search').val(), sermon: 1}, function(r) {
                    if (r) {
                        jQuery('#the-list-s').html(r);
                    };
                });
            }
        </script>
        <a name="top"></a>
        <div class="wrap">
            <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/sb-includes/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" /></a>
            <h2><?php _e('Upload Files', 'sermon-browser') ?></h2>
            <?php if (!sb_import_options_set()) {
                echo '<p class="plugin-update">';
                sb_print_import_options_message();
                echo "</p>\n";
            } ?>
            <br style="clear:both">
            <?php sb_print_upload_form(); ?>
        </div>
        <div class="wrap">
            <h2><?php _e('Unlinked files', 'sermon-browser') ?></h2>
            <br style="clear:both">
            <table class="widefat">
                <thead>
                    <tr>
                        <th width="10%" scope="col"><div style="text-align:center"><?php _e('ID', 'sermon-browser') ?></div></th>
                        <th width="50%" scope="col"><div style="text-align:center"><?php _e('File name', 'sermon-browser') ?></div></th>
                        <th width="20%" scope="col"><div style="text-align:center"><?php _e('File type', 'sermon-browser') ?></div></th>
                        <th width="20%" scope="col"><div style="text-align:center"><?php _e('Actions', 'sermon-browser') ?></div></th>
                    </tr>
                </thead>
                <tbody id="the-list-u">
                    <?php if (is_array($unlinked)): ?>
                        <?php $i = 0; foreach ($unlinked as $file): ?>
                            <tr class="file <?php echo (++$i % 2 == 0) ? 'alternate' : '' ?>" id="file<?php echo $file->id ?>">
                                <th style="text-align:center" scope="row"><?php echo $file->id ?></th>
                                <td id="<?php echo $file->id ?>"><?php echo substr($file->name, 0, strrpos($file->name, '.')) ?></td>
                                <td style="text-align:center"><?php echo isset($filetypes[substr($file->name, strrpos($file->name, '.') + 1)]['name']) ? $filetypes[substr($file->name, strrpos($file->name, '.') + 1)]['name'] : strtoupper(substr($file->name, strrpos($file->name, '.') + 1)) ?></td>
                                <td style="text-align:center">
                                    <a href="<?php echo admin_url("admin.php?page=sermon-browser/new_sermon.php&amp;getid3={$file->id}"); ?>"><?php _e('Create sermon', 'sermon-browser') ?></a> |
                                    <a id="link<?php echo $file->id; ?>" href="javascript:rename(<?php echo $file->id; ?>, '<?php echo $file->name; ?>')"><?php _e('Rename', 'sermon-browser'); ?></a> | <a onclick="return confirm('Do you really want to delete <?php echo str_replace("'", '', $file->name); ?>?');" href="javascript:kill(<?php echo $file->id; ?>, '<?php echo $file->name; ?>');"><?php _e('Delete', 'sermon-browser'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                </tbody>
            </table>
            <br style="clear:both">
            <div class="navigation">
                <div class="alignleft" id="uleft"></div>
                <div class="alignright" id="uright"></div>
            </div>
        </div>
        <a name="linked"></a>
        <div class="wrap">
            <h2><?php _e('Linked files', 'sermon-browser') ?></h2>
            <br style="clear:both">
            <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col"><div style="text-align:center"><?php _e('ID', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('File name', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('File type', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('Sermon', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('Actions', 'sermon-browser') ?></div></th>
                    </tr>
                </thead>
                <tbody id="the-list-l">
                    <?php if (is_array($linked)): ?>
                        <?php $i = 0; foreach ($linked as $file): ?>
                            <tr class="file <?php echo (++$i % 2 == 0) ? 'alternate' : '' ?>" id="file<?php echo $file->id ?>">
                                <th style="text-align:center" scope="row"><?php echo $file->id ?></th>
                                <td id="<?php echo $file->id ?>"><?php echo substr($file->name, 0, strrpos($file->name, '.')) ?></td>
                                <td style="text-align:center"><?php echo isset($filetypes[substr($file->name, strrpos($file->name, '.') + 1)]['name']) ? $filetypes[substr($file->name, strrpos($file->name, '.') + 1)]['name'] : strtoupper(substr($file->name, strrpos($file->name, '.') + 1)) ?></td>
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
                                    <a id="link<?php echo $file->id; ?>" href="javascript:rename(<?php echo $file->id; ?>, '<?php echo $file->name ?>')"><?php _e('Rename', 'sermon-browser') ?></a> | <a onclick="return deletelinked_<?php echo $file->id;?>('<?php echo str_replace("'", '', $file->name); ?>', '<?php echo str_replace("'", '', $file->title); ?>');" href="javascript:kill(<?php echo $file->id; ?>, '<?php echo $file->name; ?>');"><?php _e('Delete', 'sermon-browser'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php endif ?>
                </tbody>
            </table>
            <br style="clear:both">
            <div class="navigation">
                <div class="alignleft" id="left"></div>
                <div class="alignright" id="right"></div>
            </div>
        </div>
        <a name="search"></a>
        <div class="wrap">
            <h2><?php _e('Search for files', 'sermon-browser') ?></h2>
            <form id="searchform" name="searchform">
                <p>
                    <input type="text" size="30" value="" id="search" />
                    <input type="submit" class="button" value="<?php _e('Search', 'sermon-browser') ?> &raquo;" onclick="javascript:findNow();return false;" />
                </p>
            </form>
            <table class="widefat">
                <thead>
                    <tr>
                        <th scope="col"><div style="text-align:center"><?php _e('ID', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('File name', 'sermon-browser') ?></div></th>
                        <th scope="col"><div style="text-align:center"><?php _e('File type', 'sermon-browser') ?></div></th>
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
        <script>
            <?php if ($cntu > $sermonsPerPage): ?>
                jQuery('#uright').html('<a href="javascript:fetchU(<?php echo $sermonsPerPage ?>)">Next &raquo;</a>');
            <?php endif ?>
            <?php if ($cntl > $sermonsPerPage): ?>
                jQuery('#right').html('<a href="javascript:fetchL(<?php echo $sermonsPerPage ?>)">Next &raquo;</a>');
            <?php endif ?>
        </script>
        <?php
        if (isset($checkSermonUpload) && $checkSermonUpload === 'writeable') {
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
    }
}
