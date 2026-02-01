<?php

declare(strict_types=1);

namespace SermonBrowser\Ajax;

use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Sermon;

/**
 * Legacy AJAX handler for Sermon Browser.
 *
 * Handles AJAX requests from the old admin interface.
 * This class consolidates the logic from sb-includes/ajax.php.
 *
 * @since 0.6.0
 */
class LegacyAjaxHandler
{
    /**
     * Handle incoming AJAX request.
     *
     * This method processes various AJAX operations including:
     * - Preacher CRUD operations
     * - Service CRUD operations
     * - Series CRUD operations
     * - File CRUD operations
     * - Sermon pagination
     * - File pagination
     *
     * @return void Outputs response and terminates.
     */
    public static function handle(): void
    {
        define('SB_AJAX', true);

        // Preacher operations
        if (isset($_POST['pname'])) {
            self::handlePreacher();
            return;
        }

        // Service operations
        if (isset($_POST['sname'])) {
            self::handleService();
            return;
        }

        // Series operations
        if (isset($_POST['ssname'])) {
            self::handleSeries();
            return;
        }

        // File operations
        if (isset($_POST['fname']) && validate_file(sb_get_option('upload_dir') . $_POST['fname']) === 0) {
            self::handleFile();
            return;
        }

        // Sermon pagination
        if (isset($_POST['fetch'])) {
            self::handleSermonPagination();
            return;
        }

        // File pagination
        if (isset($_POST['fetchU']) || isset($_POST['fetchL']) || isset($_POST['search'])) {
            self::handleFilePagination();
            return;
        }

        die();
    }

    /**
     * Handle preacher CRUD operations.
     */
    private static function handlePreacher(): void
    {
        $pname = sanitize_text_field($_POST['pname']);

        if (isset($_POST['pid'])) {
            $pid = (int) $_POST['pid'];
            if (isset($_POST['del'])) {
                Preacher::delete($pid);
            } else {
                Preacher::update($pid, ['name' => $pname]);
            }
            echo 'done';
            die();
        }

        $newId = Preacher::create(['name' => $pname, 'description' => '', 'image' => '']);
        echo $newId;
        die();
    }

    /**
     * Handle service CRUD operations.
     */
    private static function handleService(): void
    {
        $sname = sanitize_text_field($_POST['sname']);
        list($sname, $stime) = explode('@', $sname);
        $sname = trim($sname);
        $stime = trim($stime);

        if (isset($_POST['sid'])) {
            $sid = (int) $_POST['sid'];
            if (isset($_POST['del'])) {
                Service::delete($sid);
            } else {
                Service::updateWithTimeShift($sid, $sname, $stime);
            }
            echo 'done';
            die();
        }

        $newId = Service::create(['name' => $sname, 'time' => $stime]);
        echo $newId;
        die();
    }

    /**
     * Handle series CRUD operations.
     */
    private static function handleSeries(): void
    {
        $ssname = sanitize_text_field($_POST['ssname']);

        if (isset($_POST['ssid'])) {
            $ssid = (int) $_POST['ssid'];
            if (isset($_POST['del'])) {
                Series::delete($ssid);
            } else {
                Series::update($ssid, ['name' => $ssname]);
            }
            echo 'done';
            die();
        }

        $newId = Series::create(['name' => $ssname, 'page_id' => 0]);
        echo $newId;
        die();
    }

    /**
     * Handle file CRUD operations.
     */
    private static function handleFile(): void
    {
        $fname = sanitize_file_name($_POST['fname']);

        if (!isset($_POST['fid'])) {
            return;
        }

        $fid = (int) $_POST['fid'];
        $oname = isset($_POST['oname']) ? sanitize_file_name($_POST['oname']) : '';

        if (isset($_POST['del'])) {
            $filePath = SB_ABSPATH . sb_get_option('upload_dir') . $fname;
            if (!file_exists($filePath) || unlink($filePath)) {
                File::delete($fid);
                echo 'deleted';
                die();
            }
            echo 'failed';
            die();
        }

        // Rename operation
        if (IS_MU) {
            $file_allowed = false;
            $allowed_extensions = explode(" ", get_site_option("upload_filetypes"));
            foreach ($allowed_extensions as $ext) {
                if (substr(strtolower($fname), -(strlen($ext) + 1)) == "." . strtolower($ext)) {
                    $file_allowed = true;
                }
            }
        } else {
            $file_allowed = true;
        }

        if (!$file_allowed) {
            echo 'forbidden';
            die();
        }

        $uploadDir = sb_get_option('upload_dir');
        if (
            (validate_file($uploadDir . $_POST['oname']) === 0) &&
            !is_writable(SB_ABSPATH . $uploadDir . $fname) &&
            rename(SB_ABSPATH . $uploadDir . $oname, SB_ABSPATH . $uploadDir . $fname)
        ) {
            File::update($fid, ['name' => $fname]);
            echo 'renamed';
            die();
        }

        echo 'failed';
        die();
    }

    /**
     * Handle sermon pagination AJAX request.
     */
    private static function handleSermonPagination(): void
    {
        wp_timezone_override_offset();

        $st = (int) $_POST['fetch'] - 1;
        $filter = [];

        if (!empty($_POST['title'])) {
            $filter['title'] = sanitize_text_field($_POST['title']);
        }
        if ($_POST['preacher'] != 0) {
            $filter['preacher_id'] = (int) $_POST['preacher'];
        }
        if ($_POST['series'] != 0) {
            $filter['series_id'] = (int) $_POST['series'];
        }

        $sermonsPerPage = sb_get_option('sermons_per_page');
        $m = Sermon::findForAdminListFiltered($filter, $sermonsPerPage, $st);
        $cnt = Sermon::countFiltered($filter);
        $i = 0;

        foreach ($m as $sermon) :
            ++$i;
            ?>
            <tr class="<?php echo $i % 2 == 0 ? 'alternate' : '' ?>">
                <th style="text-align:center" scope="row"><?php echo $sermon->id ?></th>
                <td><?php echo stripslashes($sermon->title) ?></td>
                <td><?php echo stripslashes($sermon->pname) ?></td>
                <td><?php echo ($sermon->datetime == '1970-01-01 00:00:00') ? __('Unknown', 'sermon-browser') : wp_date('d M y', strtotime($sermon->datetime)); ?></td>
                <td><?php echo stripslashes($sermon->sname) ?></td>
                <td><?php echo stripslashes($sermon->ssname) ?></td>
                <td><?php echo sb_sermon_stats($sermon->id) ?></td>
                <td style="text-align:center">
                    <?php if (current_user_can('edit_posts')) { ?>
                        <a href="<?php echo admin_url("admin.php?page=sermon-browser/new_sermon.php&mid={$sermon->id}"); ?>"><?php _e('Edit', 'sermon-browser') ?></a> | <a onclick="return confirm('Are you sure?')" href="<?php echo admin_url("admin.php?page=sermon-browser/sermon.php&mid={$sermon->id}"); ?>"><?php _e('Delete', 'sermon-browser'); ?></a> |
                    <?php } ?>
                    <a href="<?php echo sb_display_url() . sb_query_char(true) . 'sermon_id=' . $sermon->id;?>">View</a>
                </td>
            </tr>
        <?php endforeach ?>
        <script type="text/javascript">
        <?php if ($cnt < $sermonsPerPage || $cnt <= $st + $sermonsPerPage) : ?>
            jQuery('#right').css('display','none');
        <?php elseif ($cnt > $st + $sermonsPerPage) : ?>
            jQuery('#right').css('display','');
        <?php endif ?>
        </script>
        <?php
        die();
    }

    /**
     * Handle file pagination AJAX request.
     */
    private static function handleFilePagination(): void
    {
        global $filetypes;

        if (isset($_POST['fetchU'])) {
            $st = (int) $_POST['fetchU'] - 1;
            $abc = File::findUnlinkedWithTitle(sb_get_option('sermons_per_page'), $st);
        } elseif (isset($_POST['fetchL'])) {
            $st = (int) $_POST['fetchL'] - 1;
            $abc = File::findLinkedWithTitle(sb_get_option('sermons_per_page'), $st);
        } else {
            $s = sanitize_text_field($_POST['search']);
            $abc = File::searchByName($s);
        }

        $i = 0;

        if (count($abc) >= 1) :
            foreach ($abc as $file) :
                ++$i;
                $prefix = (int)($_POST['fetchU'] ?? 0) ? '' : 's';
                ?>
                <tr class="file <?php echo ($i % 2 == 0) ? 'alternate' : '' ?>" id="<?php echo $prefix ?>file<?php echo $file->id ?>">
                    <th style="text-align:center" scope="row"><?php echo $file->id ?></th>
                    <td id="<?php echo $prefix ?><?php echo $file->id ?>"><?php echo substr($file->name, 0, strrpos($file->name, '.')) ?></td>
                    <td style="text-align:center"><?php echo isset($filetypes[substr($file->name, strrpos($file->name, '.') + 1)]['name']) ? $filetypes[substr($file->name, strrpos($file->name, '.') + 1)]['name'] : strtoupper(substr($file->name, strrpos($file->name, '.') + 1)) ?></td>
                    <?php if (!isset($_POST['fetchU'])) : ?>
                        <td><?php echo stripslashes($file->title) ?></td>
                    <?php endif; ?>
                    <td style="text-align:center">
                        <script type="text/javascript" language="javascript">
                        function deletelinked_<?php echo $file->id;?>(filename, filesermon) {
                            if (confirm('Do you really want to delete '+filename+'?')) {
                                if (filesermon != '') {
                                    return confirm('This file is linked to the sermon called ['+filesermon+']. Are you sure you want to delete it?');
                                }
                                return true;
                            }
                            return false;
                        }
                        </script>
                        <?php if (isset($_POST['fetchU'])) : ?>
                            <a id="" href="<?php echo admin_url("admin.php?page=sermon-browser/new_sermon.php&amp;getid3={$file->id}"); ?>"><?php _e('Create sermon', 'sermon-browser') ?></a> |
                        <?php endif; ?>
                        <a id="link<?php echo $file->id ?>" href="javascript:rename(<?php echo $file->id ?>, '<?php echo $file->name ?>')"><?php _e('Rename', 'sermon-browser') ?></a> | <a onclick="return deletelinked_<?php echo $file->id;?>('<?php echo str_replace("'", '', $file->name) ?>', '<?php echo str_replace("'", '', $file->title) ?>');" href="javascript:kill(<?php echo $file->id ?>, '<?php echo $file->name ?>');"><?php _e('Delete', 'sermon-browser') ?></a>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php else : ?>
            <tr>
                <td><?php _e('No results', 'sermon-browser') ?></td>
            </tr>
        <?php endif;

        die();
    }
}
