<?php

/**
* Admin functions
*
* Functions required exclusively in the back end.
 *
* @package admin_functions
*/

use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\Sermon;
use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Tag;
use SermonBrowser\Facades\Book;
// Phase 2: Admin Page classes
use SermonBrowser\Admin\Pages\FilesPage;
use SermonBrowser\Admin\Pages\HelpPage;
use SermonBrowser\Admin\Pages\OptionsPage;
use SermonBrowser\Admin\Pages\PreachersPage;
use SermonBrowser\Admin\Pages\SeriesServicesPage;
use SermonBrowser\Admin\Pages\SermonEditorPage;
use SermonBrowser\Admin\Pages\SermonsPage;
use SermonBrowser\Admin\Pages\TemplatesPage;
use SermonBrowser\Admin\Pages\UninstallPage;

/**
* Adds javascript and CSS where required in admin
*/
function sb_add_admin_headers()
{
    if (isset($_REQUEST['page']) && substr($_REQUEST['page'], 14) == 'sermon-browser') {
        wp_enqueue_script('jquery');

        // Enqueue admin AJAX module (Phase 3).
        wp_enqueue_script(
            'sb-admin-ajax',
            SB_PLUGIN_URL . '/assets/js/admin-ajax.js',
            array('jquery'),
            SB_CURRENT_VERSION,
            true
        );

        // Localize nonces and i18n for AJAX handlers.
        wp_localize_script('sb-admin-ajax', 'sbAjaxSettings', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'preacherNonce' => wp_create_nonce('sb_preacher_nonce'),
            'seriesNonce'   => wp_create_nonce('sb_series_nonce'),
            'serviceNonce'  => wp_create_nonce('sb_service_nonce'),
            'fileNonce'     => wp_create_nonce('sb_file_nonce'),
            'sermonNonce'   => wp_create_nonce('sb_sermon_nonce'),
            'i18n'          => array(
                'edit'          => __('Edit', 'sermon-browser'),
                'delete'        => __('Delete', 'sermon-browser'),
                'view'          => __('View', 'sermon-browser'),
                'rename'        => __('Rename', 'sermon-browser'),
                'createSermon'  => __('Create sermon', 'sermon-browser'),
                'noResults'     => __('No results', 'sermon-browser'),
                'confirmDelete' => __('Are you sure?', 'sermon-browser'),
                'previous'      => __('&laquo; Previous', 'sermon-browser'),
                'next'          => __('Next &raquo;', 'sermon-browser'),
            ),
        ));
    }
    if (isset($_REQUEST['page']) && $_REQUEST['page'] == 'sermon-browser/new_sermon.php') {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css', array(), '1.13.2');
        wp_enqueue_style('sb_style');
    }
}

/**
* Display the options page and handle changes
*/
function sb_options()
{
    $page = new OptionsPage();
    $page->render();
}

/**
* Display uninstall screen and perform uninstall if requested
*/
function sb_uninstall()
{
    $page = new UninstallPage();
    $page->render();
}

/**
* Display the templates page and handle changes
*/
function sb_templates()
{
    $page = new TemplatesPage();
    $page->render();
}

/**
* Display the preachers page and handle changes
*/
function sb_manage_preachers()
{
    $page = new PreachersPage();
    $page->render();
}

/**
* Display services & series page and handle changes
*/
function sb_manage_everything()
{
    $page = new SeriesServicesPage();
    $page->render();
}

/**
* Display files page and handle changes
*/
function sb_files()
{
    $page = new FilesPage();
    $page->render();
}

/**
* Displays Sermons page
*/
function sb_manage_sermons()
{
    $page = new SermonsPage();
    $page->render();
}

/**
* Displays new/edit sermon page
*/
function sb_new_sermon()
{
    $page = new SermonEditorPage();
    $page->render();
}

/**
* Displays the help page
*/
function sb_help()
{
    $page = new HelpPage();
    $page->render();
}

function sb_japan()
{
    $page = new HelpPage();
    $page->renderJapan();
}
/***************************************
 ** Supplementary functions           **
 **************************************/

/**
* Displays alerts in admin for new users
*/
function sb_do_alerts()
{
    if (stripos(sb_get_option('mp3_shortcode'), '%SERMONURL%') === false) {
        echo '<div id="message" class="updated fade"><p><b>';
        _e('Error:</b> The MP3 shortcode must link to individual sermon files. You do this by including <span style="color:red">%SERMONURL%</span> in your shortcode (e.g. [audio mp3="%SERMONURL%"]). SermonBrowser will then replace %SERMONURL% with a link to each sermon.', 'sermon-browser');
        echo '</div>';
    } elseif (do_shortcode(sb_get_option('mp3_shortcode')) == sb_get_option('mp3_shortcode')) {
        echo '<div id="message" class="updated fade"><p><b>';
        _e('Error:</b> You have specified a custom MP3 shortcode, but Wordpress doesn&#146;t know how to interpret it. Make sure the shortcode is correct, and that the appropriate plugin is activated.', 'sermon-browser');
        echo '</div>';
    }
    if (sb_display_url() == "") {
        echo '<div id="message" class="updated"><p><b>' . __('Hint:', 'sermon-browser') . '</b> ' . sprintf(__('%sCreate a page%s that includes the shortcode [sermons], so that SermonBrowser knows where to display the sermons on your site.', 'sermon-browser'), '<a href="' . admin_url('page-new.php') . '">', '</a>') . '</div>';
    }
}

/**
* Show the textarea input
*/
function sb_build_textarea($name, $html)
{
    $out = '<textarea name="' . $name . '" cols="75" rows="20" style="width:100%">';
    $out .= stripslashes(str_replace('\r\n', "\n", $html));
    $out .= '</textarea>';
    echo $out;
}

/**
* Displays stats in the dashboard
*/
function sb_rightnow()
{
    $file_count = File::countByType('file');
    $output_string = '';
    if ($file_count > 0) {
        $sermon_count = Sermon::count();
        $preacher_count = Preacher::count();
        $series_count = Series::count();
        $tag_count = Tag::countNonEmpty();
        $download_count = File::getTotalDownloads();
        if ($sermon_count == 0) {
            $download_average = 0;
        } else {
            $download_average = round($download_count / $sermon_count, 1);
        }
        $most_popular = File::getMostPopularSermon();
        $output_string .= '<p class="youhave">' . __("You have") . " ";
        $output_string .= '<a href="' . admin_url('admin.php?page=sermon-browser/files.php') . '">';
        $output_string .= sprintf(_n('%s file', '%s files', $file_count), number_format($file_count)) . "</a> ";
        if ($sermon_count > 0) {
            $output_string .= __("in") . " " . '<a href="' . admin_url('admin.php?page=sermon-browser/sermon.php') . '">';
            $output_string .= sprintf(_n('%s sermon', '%s sermons', $sermon_count), number_format($sermon_count)) . "</a> ";
        }
        if ($preacher_count > 0) {
            $output_string .= __("from") . " " . '<a href="' . admin_url('admin.php?page=sermon-browser/preachers.php') . '">';
            $output_string .= sprintf(_n('%s preacher', '%s preachers', $preacher_count), number_format($preacher_count)) . "</a> ";
        }
        if ($series_count > 0) {
            $output_string .= __("in") . " " . '<a href="' . admin_url('admin.php?page=sermon-browser/manage.php') . '">';
            $output_string .= sprintf(__('%s series'), number_format($series_count)) . "</a> ";
        }
        if ($tag_count > 0) {
            $output_string .= __("using") . " " . sprintf(_n('%s tag', '%s tags', $tag_count), number_format($tag_count)) . " ";
        }
        if (substr($output_string, -1) == " ") {
            $output_string = substr($output_string, 0, -1);
        }
        if ($download_count > 0) {
            $output_string .= ". " . sprintf(_n('Only one file has been downloaded', 'They have been downloaded a total of %s times', $download_count), number_format($download_count));
        }
        if ($download_count > 1) {
            $output_string .= ", " . sprintf(_n('an average of once per sermon', 'an average of %d times per sermon', $download_average), $download_average);
            $most_popular_title = '<a href="' . sb_display_url() . sb_query_char(true) . 'sermon_id=' . $most_popular->sermon_id . '">' . stripslashes($most_popular->title) . '</a>';
            $output_string .= ". " . sprintf(__('The most popular sermon is %s, which has been downloaded %s times'), $most_popular_title, number_format($most_popular->c));
        }
        $output_string .= '.</p>';
    }
    echo $output_string;
}

/**
 * Displays sermon count in the "At a Glance" dashboard widget.
 *
 * Replaces the deprecated rightnow_end hook with dashboard_glance_items filter.
 *
 * @since 0.46.0
 * @param array $items Existing glance items.
 * @return array Modified glance items with sermon count.
 */
function sb_dashboard_glance($items)
{
    $sermon_count = Sermon::count();

    if ($sermon_count > 0) {
        $text = sprintf(
            _n('%s Sermon', '%s Sermons', $sermon_count, 'sermon-browser'),
            number_format_i18n($sermon_count)
        );
        $items[] = sprintf(
            '<a class="sermon-count" href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=sermon-browser/sermon.php')),
            esc_html($text)
        );
    }

    return $items;
}

/**
* Find new files uploaded by FTP
*/
function sb_scan_dir()
{
    File::deleteEmptyUnlinked();
    $fileNames = File::findAllFileNames();
    $dir = SB_ABSPATH . sb_get_option('upload_dir');
    foreach ($fileNames as $fileName) {
        if (!file_exists($dir . $fileName)) {
            File::deleteUnlinkedByName($fileName);
        }
    }

    if ($dh = @opendir($dir)) {
        while (false !== ($file = readdir($dh))) {
            if ($file != "." && $file != ".." && !is_dir($dir . $file) && !in_array($file, $fileNames)) {
                File::create(['type' => 'file', 'name' => $file, 'sermon_id' => 0, 'count' => 0, 'duration' => 0]);
            }
        }
           closedir($dh);
    }
}

/**
* Check to see if upload folder is writeable
*
* @return string 'writeable/unwriteable/notexist'
*/

function sb_checkSermonUploadable($foldername = "")
{
    $sermonUploadDir = SB_ABSPATH . sb_get_option('upload_dir') . $foldername;
    if (is_dir($sermonUploadDir)) {
        //Dir exist
        $fp = @fopen($sermonUploadDir . 'sermontest.txt', 'w');
        if ($fp) {
            //Delete this test file
            fclose($fp);
            unset($fp);
            @unlink($sermonUploadDir . 'sermontest.txt');
            return 'writeable';
        } else {
            return 'unwriteable';
        }
    } else {
        return 'notexist';
    }
    return false;
}

/**
* Delete any unused tags
*/
function sb_delete_unused_tags()
{
    Tag::deleteUnused();
}

/**
* Returns true if any ID3 import options have been selected
*
* @return boolean
*/
function sb_import_options_set()
{
    if (!sb_get_option('import_title') && !sb_get_option('import_artist') && !sb_get_option('import_album') && !sb_get_option('import_comments') && (!sb_get_option('import_filename') || sb_get_option('import_filename') == 'none')) {
        return false;
    } else {
        return true;
    }
}

/**
* Displays notice if ID3 import options have not been set
*/
function sb_print_import_options_message($long = false)
{
    if (!sb_import_options_set()) {
        if ($long) {
            _e('SermonBrowser can automatically pre-fill this form by reading ID3 tags from MP3 files.', 'sermon-browser');
            echo ' ';
        }
        printf(__('You will need to set the %s before you can import MP3s and pre-fill the Add Sermons form.', 'sermon-browser'), '<a href="' . admin_url('admin.php?page=sermon-browser/options.php') . '">' . __('import options', 'sermon-browser') . '</a>');
    }
}

/**
* echoes the upload form
*/
function sb_print_upload_form()
{
    ?>
    <table width="100%" cellspacing="2" cellpadding="5" class="widefat">
        <form method="post" enctype="multipart/form-data" action ="<?php echo admin_url('admin.php?page=sermon-browser/files.php'); ?>" >
        <thead>
        <tr>
            <th scope="col" colspan="3"><?php if (sb_import_options_set()) {
                printf(__("Select an MP3 file here to have the %s form pre-filled using ID3 tags.", 'sermon-browser'), "<a href=\"" . admin_url('admin.php?page=sermon-browser/new_sermon.php') . "\">" . __('Add Sermons', 'sermon-browser') . '</a>');
                                        } else {
                                            _e('Upload file', 'sermon-browser');
                                        }?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <th nowrap style="width:20em" valign="top" scope="row"><?php _e('File to upload', 'sermon-browser') ?>: </th>
    <?php
    $checkSermonUpload = sb_checkSermonUploadable();
    if ($checkSermonUpload == 'writeable') {
        ?>
            <td width ="40"><input type="file" size="40" value="" name="upload" /></td>
            <td class="submit"><input type="submit" name="save" value="<?php _e('Upload', 'sermon-browser') ?> &raquo;" /></td>
        <?php
    } elseif (IS_MU) {
        ?>
            <td><?php _e('Upload is disabled. Please contact your systems administrator.', 'sermon-browser');?></td>
        <?php
    } else {
        ?>
            <td><?php _e('Upload is disabled. Please check your folder setting in Options.', 'sermon-browser');?></td>
        <?php
    }
    ?>
        </tr>
    <?php if (sb_import_options_set()) { ?>
        <tr>
            <th nowrap valign="top" scope="row"><?php _e('URL to import', 'sermon-browser') ?>: </th>
            <td>
                <input type="text" size="40" value="" name="url"/><br/>
                <span style="line-height: 29px"><input type="radio" name="import_type" value="remote" checked="checked" /><?php _e('Link to remote file', 'sermon-browser') ?> <input type="radio" name="import_type" value="download" /><?php _e('Copy remote file to server', 'sermon-browser') ?></span>
            </td>
            <td class="submit"><input type="submit" name="import_url" value="<?php _e('Import', 'sermon-browser') ?> &raquo;" /></td>
        </tr>
    <?php } ?>
    </form>
    <?php if ($_GET['page'] == 'sermon-browser/new_sermon.php') { ?>
        <form method="get" action="<?php echo admin_url('admin.php?page=sermon-browser/new_sermon.php');?>">
        <input type="hidden" name="page" value="sermon-browser/new_sermon.php" />
        <tr>
            <th nowrap valign="top" scope="row"><?php _e('Choose existing file', 'sermon-browser') ?>: </th>
            <td>
                <select name="getid3">
                    <?php
                        $files = File::findUnlinked();
                        echo count($files) == 0 ? '<option value="0">No files found</option>' : '<option value="0"></option>';
                    foreach ($files as $file) { ?>
                            <option value="<?php echo $file->id ?>"><?php echo $file->name ?></option>
                    <?php } ?>
                </select>
            </td>
            <td class="submit"><input type="submit" value="<?php _e('Select', 'sermon-browser') ?> &raquo;" /></td>
        </tr>
    </form>
    <?php } ?>
        </tbody>
</table>
<?php }

/**
 * Add help tabs to SermonBrowser admin pages.
 *
 * Phase 1: Replaces deprecated contextual_help filter with Help Tabs API.
 *
 * @param WP_Screen $screen Current screen object.
 */
function sb_add_help_tabs($screen)
{
    if (!isset($_GET['page'])) {
        return;
    }

    $page = $_GET['page'];

    // Only process sermon-browser pages.
    if (strpos($page, 'sermon-browser/') !== 0) {
        return;
    }

    $content = '';
    switch ($page) {
        case 'sermon-browser/sermon.php':
            $content = __('From this page you can edit or delete any of your sermons. The most recent sermons are found at the top. Use the filter options to quickly find the one you want.', 'sermon-browser');
            break;
        case 'sermon-browser/new_sermon.php':
        case 'sermon-browser/files.php':
        case 'sermon-browser/preachers.php':
        case 'sermon-browser/manage.php':
        case 'sermon-browser/options.php':
            $content = __('It&#146;s important that these options are set correctly, as otherwise SermonBrowser won&#146;t behave as you expect.', 'sermon-browser') . '<ul>';
            $content .= '<li>' . __('The upload folder would normally be <b>wp-content/uploads/sermons</b>', 'sermon-browser') . '</li>';
            $content .= '<li>' . __('You should only change the public podcast feed if you re-direct your podcast using a service like Feedburner. Otherwise it should be the same as the private podcast feed.', 'sermon-browser') . '</li>';
            $content .= '<li>' . __('The MP3 shortcode you need will be in the documation of your favourite MP3 plugin. Use the tag %SERMONURL% in place of the URL of the MP3 file (e.g. [haiku url="%SERMONURL%"] or [audio:%SERMONURL%]).', 'sermon-browser') . '</li></ul>';
            break;
        case 'sermon-browser/templates.php':
            $content = sprintf(__('Template editing is one of the most powerful features of SermonBrowser. Be sure to look at the complete list of %stemplate tags%s.', 'sermon-browser'), '<a href="http://www.sermonbrowser.com/customisation/">', '</a>');
            break;
    }

    if (!empty($content)) {
        $screen->add_help_tab(array(
            'id'      => 'sermon-browser-help',
            'title'   => __('SermonBrowser Help', 'sermon-browser'),
            'content' => '<p>' . $content . '</p>',
        ));
    }

    // Add sidebar with useful links.
    $sidebar = '<p><strong>' . __('For more information:', 'sermon-browser') . '</strong></p>';
    $sidebar .= '<p><a href="http://www.sermonbrowser.com/tutorials/">' . __('Tutorial Screencasts', 'sermon-browser') . '</a></p>';
    $sidebar .= '<p><a href="http://www.sermonbrowser.com/faq/">' . __('Frequently Asked Questions', 'sermon-browser') . '</a></p>';
    $sidebar .= '<p><a href="http://www.sermonbrowser.com/forum/">' . __('Support Forum', 'sermon-browser') . '</a></p>';
    $sidebar .= '<p><a href="http://www.sermonbrowser.com/customisation/">' . __('Shortcode syntax', 'sermon-browser') . '</a></p>';
    $sidebar .= '<p><a href="http://www.sermonbrowser.com/donate/">' . __('Donate', 'sermon-browser') . '</a></p>';

    $screen->set_help_sidebar($sidebar);
}

// Keep old function for backward compatibility but mark as deprecated.
function sb_add_contextual_help($help)
{
    _deprecated_function(__FUNCTION__, '0.46.0', 'sb_add_help_tabs');
    return $help;
}
?>