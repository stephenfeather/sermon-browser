<?php

/**
 * Sermon Editor Page.
 *
 * Handles the Add/Edit Sermon admin page.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

use SermonBrowser\Facades\Service;
use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Tag;
use SermonBrowser\Facades\Sermon;
use SermonBrowser\Facades\Book;

/**
 * Class SermonEditorPage
 *
 * Renders and handles the sermon editor form.
 */
class SermonEditorPage
{
    /**
     * Allowed HTML tags for kses filtering.
     *
     * @var array
     */
    private array $allowedPostTags;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $allowedposttags;
        $this->allowedPostTags = $allowedposttags ?? [];
    }

    /**
     * Render the sermon editor page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!(current_user_can('publish_posts') || current_user_can('publish_pages'))) {
            wp_die(__("You do not have the correct permissions to edit or create sermons", 'sermon-browser'));
        }

        include_once SB_ABSPATH . '/wp-includes/kses.php';
        sb_scan_dir();
        $translated_books = array_combine(sb_get_default('eng_bible_books'), sb_get_default('bible_books'));

        // Handle form submission.
        $error = $this->handlePost();

        // Handle ID3 tag import.
        $id3_tags = $this->handleId3Import();

        // Load data for the form.
        $formData = $this->loadFormData();

        // Render the form.
        $this->renderForm($formData, $id3_tags, $translated_books, $error);
    }

    /**
     * Handle POST submission.
     *
     * @return bool Whether an error occurred.
     */
    private function handlePost(): bool
    {
        global $allowedposttags;
        $error = false;

        if (!isset($_POST['save']) || !isset($_POST['title'])) {
            return false;
        }

        // Verify nonce.
        if (!wp_verify_nonce($_REQUEST['sermon_browser_save_nonce'] ?? '', 'sermon_browser_save')) {
            wp_die(__("You do not have the correct permissions to edit or create sermons", 'sermon-browser'));
        }

        // Prepare data.
        $title = sanitize_text_field($_POST['title']);
        $preacher_id = (int) $_POST['preacher'];
        $service_id = (int) $_POST['service'];
        $series_id = (int) $_POST['series'];
        $time = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';

        // Process Bible passages.
        $startz = $endz = [];
        $startBooks = $_POST['start']['book'] ?? [];
        for ($foo = 0; $foo < count($startBooks); $foo++) {
            if (
                !empty($_POST['start']['chapter'][$foo]) && !empty($_POST['end']['chapter'][$foo])
                && !empty($_POST['start']['verse'][$foo]) && !empty($_POST['end']['verse'][$foo])
            ) {
                $startz[] = [
                    'book' => sanitize_text_field($_POST['start']['book'][$foo]),
                    'chapter' => (int) $_POST['start']['chapter'][$foo],
                    'verse' => (int) $_POST['start']['verse'][$foo],
                ];
                $endz[] = [
                    'book' => sanitize_text_field($_POST['end']['book'][$foo]),
                    'chapter' => (int) $_POST['end']['chapter'][$foo],
                    'verse' => (int) $_POST['end']['verse'][$foo],
                ];
            }
        }
        $start = serialize($startz);
        $end = serialize($endz);

        // Process date.
        $date = strtotime($_POST['date']);
        $override = (isset($_POST['override']) && $_POST['override'] === 'on') ? 1 : 0;
        if ($date) {
            if (!$override) {
                $service = Service::find($service_id);
                $service_time = $service ? $service->time : null;
                if ($service_time) {
                    $date = $date - strtotime('00:00') + strtotime($service_time);
                }
            } else {
                $date = $date - strtotime('00:00') + strtotime($_POST['time']);
            }
            $date = date('Y-m-d H:i:s', $date);
        } else {
            $date = '1970-01-01 00:00';
        }

        // Filter description.
        if (!current_user_can('unfiltered_html')) {
            $description = wp_kses($_POST['description'], $allowedposttags);
        } else {
            $description = $_POST['description'];
        }

        // Insert or update sermon.
        $id = $this->saveSermon($title, $preacher_id, $service_id, $series_id, $date, $start, $end, $description, $time, $override);

        // Save Bible passages.
        $this->saveBiblePassages($id, $startz, $endz);

        // Save attachments.
        $error = $this->saveAttachments($id) || $error;

        // Save tags.
        $this->saveTags($id);

        // Redirect on success.
        if (!$error) {
            echo "<script>document.location = '" . admin_url('admin.php?page=sermon-browser/sermon.php&saved=true') . "';</script>";
            die();
        }

        return $error;
    }

    /**
     * Save the sermon to the database.
     *
     * @param string $title Sermon title.
     * @param int $preacher_id Preacher ID.
     * @param int $service_id Service ID.
     * @param int $series_id Series ID.
     * @param string $date Formatted date.
     * @param string $start Serialized start passages.
     * @param string $end Serialized end passages.
     * @param string $description Sermon description.
     * @param string $time Service time.
     * @param int $override Override flag.
     * @return int The sermon ID.
     */
    private function saveSermon(
        string $title,
        int $preacher_id,
        int $service_id,
        int $series_id,
        string $date,
        string $start,
        string $end,
        string $description,
        string $time,
        int $override
    ): int {
        if (!isset($_GET['mid']) || !$_GET['mid']) {
            // New sermon.
            if (!current_user_can('publish_pages')) {
                wp_die(__("You do not have the correct permissions to create sermons", 'sermon-browser'));
            }
            return Sermon::create([
                'title' => $title,
                'preacher_id' => $preacher_id,
                'datetime' => $date,
                'service_id' => $service_id,
                'series_id' => $series_id,
                'start' => $start,
                'end' => $end,
                'description' => $description,
                'time' => $time,
                'override' => $override,
            ]);
        } else {
            // Edit existing.
            if (!current_user_can('publish_posts')) {
                wp_die(__("You do not have the correct permissions to edit sermons", 'sermon-browser'));
            }
            $id = (int) $_GET['mid'];
            Sermon::update($id, [
                'title' => $title,
                'preacher_id' => $preacher_id,
                'datetime' => $date,
                'series_id' => $series_id,
                'start' => $start,
                'end' => $end,
                'description' => $description,
                'time' => $time,
                'service_id' => $service_id,
                'override' => $override,
            ]);
            File::unlinkFromSermon($id);
            File::deleteNonFilesBySermon($id);
            return $id;
        }
    }

    /**
     * Save Bible passages for a sermon.
     *
     * @param int $id Sermon ID.
     * @param array $startz Start passages.
     * @param array $endz End passages.
     * @return void
     */
    private function saveBiblePassages(int $id, array $startz, array $endz): void
    {
        Book::deleteBySermonId($id);

        foreach ($startz as $i => $st) {
            Book::insertPassageRef(
                $st['book'],
                (string) $st['chapter'],
                (string) $st['verse'],
                $i,
                'start',
                $id
            );
        }

        foreach ($endz as $i => $ed) {
            Book::insertPassageRef(
                $ed['book'],
                (string) $ed['chapter'],
                (string) $ed['verse'],
                $i,
                'end',
                $id
            );
        }
    }

    /**
     * Save attachments for a sermon.
     *
     * @param int $id Sermon ID.
     * @return bool Whether an error occurred.
     */
    private function saveAttachments(int $id): bool
    {
        global $allowedposttags;
        $error = false;

        // Handle file attachments.
        foreach ($_POST['file'] ?? [] as $uid => $file) {
            if ($file != 0) {
                File::linkToSermon((int) sanitize_file_name($file), $id);
            } elseif (isset($_FILES['upload']['error'][$uid]) && $_FILES['upload']['error'][$uid] === UPLOAD_ERR_OK) {
                $error = $this->handleFileUpload($id, $uid) || $error;
            }
        }

        // Handle URLs.
        foreach ((array) ($_POST['url'] ?? []) as $urlz) {
            if (!empty($urlz)) {
                File::create([
                    'type' => 'url',
                    'name' => esc_url($urlz),
                    'sermon_id' => $id,
                    'count' => 0,
                    'duration' => 0,
                ]);
            }
        }

        // Handle embed code.
        foreach ((array) ($_POST['code'] ?? []) as $code) {
            if (!empty($code)) {
                $embed_allowedposttags = $allowedposttags;
                $embed_allowedposttags['iframe'] = [
                    'width' => true,
                    'height' => true,
                    'src' => true,
                    'frameborder' => true,
                    'allowfullscreen' => true,
                    'style' => true,
                    'name' => true,
                    'id' => true,
                    'align' => true,
                    'sandbox' => true,
                    'srcdoc' => true,
                ];
                $code = base64_encode(wp_kses(stripslashes($code), $embed_allowedposttags));
                File::create([
                    'type' => 'code',
                    'name' => $code,
                    'sermon_id' => $id,
                    'count' => 0,
                    'duration' => 0,
                ]);
            }
        }

        return $error;
    }

    /**
     * Handle a single file upload.
     *
     * @param int $id Sermon ID.
     * @param int $uid Upload index.
     * @return bool Whether an error occurred.
     */
    private function handleFileUpload(int $id, int $uid): bool
    {
        $filename = basename($_FILES['upload']['name'][$uid]);

        // Check file type for multisite.
        if (IS_MU) {
            $file_allowed = false;
            $allowed_extensions = explode(' ', get_site_option('upload_filetypes'));
            foreach ($allowed_extensions as $ext) {
                if (substr(strtolower($filename), -(strlen($ext) + 1)) === '.' . strtolower($ext)) {
                    $file_allowed = true;
                }
            }
        } else {
            $file_allowed = true;
        }

        if (!$file_allowed) {
            @unlink($_FILES['upload']['tmp_name'][$uid]);
            echo '<div id="message" class="updated fade"><p><b>' . __('You are not permitted to upload files of that type.', 'sermon-browser') . '</b></div>';
            return true;
        }

        $prefix = '';
        $dest = SB_ABSPATH . sb_get_option('upload_dir') . $prefix . $filename;

        if (
            !File::existsByName($filename)
            && move_uploaded_file($_FILES['upload']['tmp_name'][$uid], $dest)
        ) {
            $filename = $prefix . $filename;
            File::create([
                'type' => 'file',
                'name' => $filename,
                'sermon_id' => $id,
                'count' => 0,
                'duration' => 0,
            ]);
            return false;
        } else {
            echo '<div id="message" class="updated fade"><p><b>' . esc_html($filename) . ' ' . esc_html__('already exists.', 'sermon-browser') . '</b></div>';
            return true;
        }
    }

    /**
     * Save tags for a sermon.
     *
     * @param int $id Sermon ID.
     * @return void
     */
    private function saveTags(int $id): void
    {
        $tags = explode(',', $_POST['tags'] ?? '');
        Tag::detachAllFromSermon($id);

        foreach ($tags as $tag) {
            $clean_tag = sanitize_text_field($tag);
            if (empty($clean_tag)) {
                continue;
            }

            $tag_id = Tag::findOrCreate($clean_tag);
            Tag::attachToSermon($id, $tag_id);
        }

        sb_delete_unused_tags();
    }

    /**
     * Handle ID3 tag import from audio files.
     *
     * @return array Imported ID3 tags.
     */
    private function handleId3Import(): array
    {
        $id3_tags = [];

        if (!isset($_GET['getid3'])) {
            return $id3_tags;
        }

        $file_data = File::find((int) $_GET['getid3']);

        if ($file_data === null) {
            return $id3_tags;
        }

        if (!class_exists('getID3')) {
            require ABSPATH . WPINC . '/ID3/getid3.php';
        }

        $getID3 = new \getID3();

        if ($file_data->type === 'url') {
            $id3_raw_tags = $this->analyzeRemoteFile($getID3, $file_data->name);
            $filename = substr($file_data->name, strrpos($file_data->name, '/') + 1);
        } else {
            $filename = $file_data->name;
            $id3_raw_tags = $getID3->analyze(realpath(SB_ABSPATH . sb_get_option('upload_dir') . $filename));
        }

        if (!isset($id3_raw_tags['tags'])) {
            echo '<div id="message" class="updated fade"><p><b>' . __('No ID3 tags found.', 'sermon-browser');
            if ($file_data->type === 'url') {
                echo ' Remote files must have id3v2 tags.';
            }
            echo '</b></div>';
        }

        \getid3_lib::CopyTagsToComments($id3_raw_tags);

        // Import enabled fields.
        if (sb_get_option('import_title')) {
            $id3_tags['title'] = $id3_raw_tags['comments_html']['title'][0] ?? '';
        }
        if (sb_get_option('import_comments')) {
            $id3_tags['description'] = $id3_raw_tags['comments_html']['comments'][0] ?? '';
        }
        if (sb_get_option('import_album')) {
            $id3_tags['series'] = $this->importSeries($id3_raw_tags['comments_html']['album'][0] ?? '');
        }
        if (sb_get_option('import_artist')) {
            $id3_tags['preacher'] = $this->importPreacher($id3_raw_tags['comments_html']['artist'][0] ?? '');
        }

        // Import date from filename.
        $date_format = sb_get_option('import_filename');
        if ($date_format !== '') {
            $id3_tags['date'] = $this->parseDateFromFilename($filename, $date_format);
        }

        return $id3_tags;
    }

    /**
     * Analyze a remote file for ID3 tags.
     *
     * @param \getID3 $getID3 GetID3 instance.
     * @param string $url Remote file URL.
     * @return array ID3 tags.
     */
    private function analyzeRemoteFile(\getID3 $getID3, string $url): array
    {
        $sermonUploadDir = SB_ABSPATH . sb_get_option('upload_dir');
        $tempfilename = $sermonUploadDir . sb_generate_temp_suffix(2) . '.mp3';

        $tempfile = @fopen($tempfilename, 'wb');
        if (!$tempfile) {
            return [];
        }

        $remote_file = @fopen($url, 'r');
        if (!$remote_file) {
            fclose($tempfile);
            return [];
        }

        $remote_contents = '';
        while (!feof($remote_file)) {
            $remote_contents .= fread($remote_file, 8192);
            if (strlen($remote_contents) > 65536) {
                break;
            }
        }

        fwrite($tempfile, $remote_contents);
        fclose($remote_file);
        fclose($tempfile);

        $id3_raw_tags = $getID3->analyze(realpath($tempfilename));
        unlink($tempfilename);

        return $id3_raw_tags;
    }

    /**
     * Import or create a series from ID3 album tag.
     *
     * @param string $album Album name.
     * @return int|string Series ID or empty string.
     */
    private function importSeries(string $album)
    {
        if ($album === '') {
            return '';
        }

        return Series::findOrCreate($album);
    }

    /**
     * Import or create a preacher from ID3 artist tag.
     *
     * @param string $artist Artist name.
     * @return int|string Preacher ID or empty string.
     */
    private function importPreacher(string $artist)
    {
        if ($artist === '') {
            return '';
        }

        return Preacher::findOrCreate($artist);
    }

    /**
     * Parse a date from a filename.
     *
     * @param string $filename The filename.
     * @param string $format Date format (uk, us, int).
     * @return string Formatted date or empty string.
     */
    private function parseDateFromFilename(string $filename, string $format): string
    {
        $filename = substr($filename, 0, strrpos($filename, '.'));
        $filename = str_replace('--', '-', str_replace('/', '-', $filename));
        $filename = trim(preg_replace('/[^0-9-]/', '', $filename), '-');
        $date = explode('-', $filename);

        if (count($date) < 3) {
            return '';
        }

        switch ($format) {
            case 'uk':
                return date('Y-m-d', mktime(0, 0, 0, (int) $date[1], (int) $date[0], (int) $date[2]));
            case 'us':
                return date('Y-m-d', mktime(0, 0, 0, (int) $date[0], (int) $date[1], (int) $date[2]));
            case 'int':
                return date('Y-m-d', mktime(0, 0, 0, (int) $date[1], (int) $date[2], (int) $date[0]));
            default:
                return '';
        }
    }

    /**
     * Load data needed for the form.
     *
     * @return array Form data.
     */
    private function loadFormData(): array
    {
        $preachers = Preacher::findAllSorted();
        $services = Service::findAllSorted();
        $series = Series::findAllSorted();
        $files = File::findUnlinked();

        // Sync files - remove entries for files that no longer exist.
        $wanted = [-1];
        foreach ((array) $files as $k => $file) {
            if (!file_exists(SB_ABSPATH . sb_get_option('upload_dir') . $file->name)) {
                $wanted[] = $file->id;
                unset($files[$k]);
            }
        }

        // Build service time array.
        $timeArr = '';
        foreach ($services as $service) {
            $timeArr .= "timeArr[{$service->id}] = '{$service->time}';";
        }

        // Load existing sermon data if editing.
        $curSermon = null;
        $startArr = [];
        $endArr = [];
        $tags = '';

        if (isset($_GET['mid'])) {
            $mid = (int) $_GET['mid'];
            $curSermon = Sermon::find($mid);
            $files = File::findBySermonOrUnlinked($mid);
            $startArr = unserialize($curSermon->start) ?: [];
            $endArr = unserialize($curSermon->end) ?: [];

            $rawtags = Tag::findBySermon($mid);
            $tagNames = [];
            foreach ($rawtags as $tag) {
                $tagNames[] = $tag->name;
            }
            $tags = implode(', ', $tagNames);
        }

        return [
            'preachers' => $preachers,
            'services' => $services,
            'series' => $series,
            'files' => $files,
            'timeArr' => $timeArr,
            'curSermon' => $curSermon,
            'startArr' => $startArr,
            'endArr' => $endArr,
            'tags' => $tags,
            'books' => sb_get_default('eng_bible_books'),
        ];
    }

    /**
     * Render the sermon editor form.
     *
     * @param array $formData Form data.
     * @param array $id3_tags Imported ID3 tags.
     * @param array $translated_books Translated Bible book names.
     * @param bool $error Whether an error occurred.
     * @return void
     */
    private function renderForm(array $formData, array $id3_tags, array $translated_books, bool $error): void
    {
        // Extract form data for template.
        extract($formData);

        // The form HTML is complex and contains inline PHP/JS.
        // For maintainability, we keep it in a separate method.
        $this->renderFormHtml($formData, $id3_tags, $translated_books);
    }

    /**
     * Render the form HTML.
     *
     * @param array $formData Form data.
     * @param array $id3_tags Imported ID3 tags.
     * @param array $translated_books Translated Bible book names.
     * @return void
     */
    private function renderFormHtml(array $formData, array $id3_tags, array $translated_books): void
    {
        // Extract variables for the template.
        $preachers = $formData['preachers'];
        $services = $formData['services'];
        $series = $formData['series'];
        $files = $formData['files'];
        $timeArr = $formData['timeArr'];
        $curSermon = $formData['curSermon'];
        $startArr = $formData['startArr'];
        $endArr = $formData['endArr'];
        $tags = $formData['tags'];
        $books = $formData['books'];
        $mid = isset($_GET['mid']) ? (int) $_GET['mid'] : null;

        // Include the template.
        // For now, we output the HTML directly. In a future refactor,
        // this could be moved to a separate template file.
        ?>
        <script type="text/javascript">
            var timeArr = new Array();
            <?php echo $timeArr ?>
            function createNewPreacher(s) {
                if (jQuery('#preacher')[0].value != 'newPreacher') return;
                var p = prompt("<?php _e("New preacher's name?", 'sermon-browser')?>", "<?php _e("Preacher's name", 'sermon-browser')?>");
                if (p != null) {
                    jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/sermon.php'); ?>', {pname: p, sermon: 1}, function(r) {
                        if (r) {
                            jQuery('#preacher option:first').before('<option value="' + r + '">' + p + '</option>');
                            jQuery("#preacher option[value='" + r + "']").prop('selected', true);
                        };
                    });
                }
            }
            function createNewService(s) {
                if (jQuery('#service')[0].value != 'newService') {
                    if (!jQuery('#override')[0].checked) {
                        jQuery('#time').val(timeArr[jQuery('#service')[0].value]).prop('disabled', true);
                    }
                    return;
                }
                var s = 'lol';
                while ((s.indexOf('@') == -1) || (s.match(/(.*?)@(.*)/)[2].match(/[0-9]{1,2}:[0-9]{1,2}/) == null)) {
                    s = prompt("<?php _e("New service's name @ default time?", 'sermon-browser')?>", "<?php _e("Service's name @ 18:00", 'sermon-browser')?>");
                    if (s == null) { break; }
                }
                if (s != null) {
                    jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/sermon.php'); ?>', {sname: s, sermon: 1}, function(r) {
                        if (r) {
                            jQuery('#service option:first').before('<option value="' + r + '">' + s.match(/(.*?)@/)[1] + '</option>');
                            jQuery("#service option[value='" + r + "']").prop('selected', true);
                            jQuery('#time').val(s.match(/(.*?)@\s*(.*)/)[2]);
                        };
                    });
                }
            }
            function createNewSeries(s) {
                if (jQuery('#series')[0].value != 'newSeries') return;
                var ss = prompt("<?php _e("New series' name?", 'sermon-browser')?>", "<?php _e("Series' name", 'sermon-browser')?>");
                if (ss != null) {
                    jQuery.post('<?php echo admin_url('admin.php?page=sermon-browser/sermon.php'); ?>', {ssname: ss, sermon: 1}, function(r) {
                        if (r) {
                            jQuery('#series option:first').before('<option value="' + r + '">' + ss + '</option>');
                            jQuery("#series option[value='" + r + "']").prop('selected', true);
                        };
                    });
                }
            }
            function addPassage() {
                var p = jQuery('#passage').clone();
                p.attr('id', 'passage' + gpid);
                jQuery('tr:first td:first', p).prepend('[<a href="javascript:removePassage(' + gpid++ + ')">x</a>] ');
                jQuery("select", p).attr('value', '');
                jQuery("input", p).attr('value', '');
                jQuery('.passage:last').after(p);
            }
            function removePassage(id) {
                jQuery('#passage' + id).remove();
            }
            function syncBook(s) {
                var slc = jQuery(s)[0].value;
                jQuery('.passage').each(function(i) {
                    if (this == jQuery(s).parents('.passage')[0]) {
                        jQuery('.end').each(function(j) {
                            if (i == j) {
                                jQuery("option[value='" + slc + "']", this).prop('selected', true);
                            }
                        });
                    }
                });
            }

            function addFile() {
                var f = jQuery('#choosefile').clone();
                f.attr('id', 'choose' + gfid);
                jQuery(".choosefile", f).attr('name', 'choose' + gfid);
                jQuery("td", f).css('display', 'none');
                jQuery("td:first", f).css('display', '');
                jQuery('th', f).prepend('[<a href="javascript:removeFile(' + gfid++ + ')">x</a>] ');
                jQuery("option[value='0']", f).prop('selected', true);
                jQuery("input", f).val('');
                jQuery('.choose:last').after(f);

            }
            function removeFile(id) {
                jQuery('#choose' + id).remove();
            }
            function doOverride(id) {
                var chk = jQuery('#override')[0].checked;
                if (chk) {
                    jQuery('#time').removeClass('gray').prop('disabled', false);
                } else {
                    jQuery('#time').addClass('gray').val(timeArr[jQuery('#service')[0].value]).prop('disabled', true);
                }
            }
            var gfid = 0;
            var gpid = 0;

            function chooseType(id, type){
                jQuery("#"+id + " td").css("display", "none");
                jQuery("#"+id + " ."+type).css("display", "");
                jQuery("#"+id + " td input").val('');
                jQuery("#"+id + " td select").val('0');
            }
        </script>
        <?php sb_do_alerts(); ?>
        <div class="wrap">
            <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" /></a>
            <h2><?php echo isset($_GET['mid']) ? __('Edit Sermon', 'sermon-browser') : __('Add Sermon', 'sermon-browser'); ?></h2>
            <?php if (!isset($_GET['mid']) && !isset($_GET['getid3']) && sb_get_option('import_prompt')) {
                if (!sb_import_options_set()) {
                    echo '<p class="plugin-update">';
                    sb_print_import_options_message(true);
                    echo "</p>\n";
                } else {
                    sb_print_upload_form();
                }
            } ?>
            <br/>
            <form method="post" enctype="multipart/form-data">
            <fieldset>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th scope="col" colspan="2"><?php _e('Enter sermon details', 'sermon-browser') ?></th>
                        </tr>
                    </thead>
                    <tr>
                        <td>
                            <strong><?php _e('Title', 'sermon-browser') ?></strong>
                            <div>
                                <input type="text" value="<?php if (isset($id3_tags['title'])) {
                                    echo $id3_tags['title'];
                                                          } elseif (isset($curSermon->title)) {
                                                              echo htmlspecialchars(stripslashes($curSermon->title));
                                                          } ?>" name="title" size="60" style="width:400px;" />
                            </div>
                        </td>
                        <td>
                            <strong><?php _e('Tags (comma separated)', 'sermon-browser') ?></strong>
                            <div>
                                <input type="text" name="tags" value="<?php echo isset($tags) ? stripslashes($tags) : ''?>" style="width:400px" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong><?php _e('Preacher', 'sermon-browser') ?></strong><br/>
                                <select id="preacher" name="preacher" onchange="createNewPreacher(this)">
                                    <?php if (count($preachers) == 0) : ?>
                                        <option value="" selected="selected"></option>
                                    <?php else : ?>
                                        <?php foreach ($preachers as $preacher) :
                                            if (isset($id3_tags['preacher'])) {
                                                $preacher_id = $id3_tags['preacher'];
                                            } elseif (isset($curSermon->preacher_id)) {
                                                $preacher_id = $curSermon->preacher_id;
                                            } else {
                                                $preacher_id = -1;
                                            } ?>
                                        <option value="<?php echo $preacher->id ?>" <?php echo $preacher->id == $preacher_id ? 'selected="selected"' : ''?>><?php echo htmlspecialchars(stripslashes($preacher->name), ENT_QUOTES) ?></option>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                    <option value="newPreacher"><?php _e('Create new preacher', 'sermon-browser') ?></option>
                                </select>
                        </td>
                        <td>
                            <strong><?php _e('Series', 'sermon-browser') ?></strong><br/>
                            <select id="series" name="series" onchange="createNewSeries(this)">
                                <?php if (count($series) == 0) : ?>
                                    <option value="" selected="selected"></option>
                                <?php else : ?>
                                    <?php foreach ($series as $item) :
                                        if (isset($id3_tags['series'])) {
                                            $series_id = $id3_tags['series'];
                                        } elseif (isset($curSermon->series_id)) {
                                            $series_id = $curSermon->series_id;
                                        } else {
                                            $series_id = -1;
                                        } ?>
                                        <option value="<?php echo $item->id ?>" <?php echo $item->id == $series_id ? 'selected="selected"' : '' ?>><?php echo htmlspecialchars(stripslashes($item->name), ENT_QUOTES) ?></option>
                                    <?php endforeach ?>
                                <?php endif ?>
                                <option value="newSeries"><?php _e('Create new series', 'sermon-browser') ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td style="overflow: visible">
                            <strong><?php _e('Date', 'sermon-browser') ?></strong> (yyyy-mm-dd)
                            <div>
                                <input type="text" id="date" name="date" value="<?php if ((isset($curSermon->datetime) && $curSermon->datetime != '1970-01-01 00:00:00') || isset($id3_tags['date'])) {
                                    echo isset($id3_tags['date']) ? $id3_tags['date'] : substr(stripslashes($curSermon->datetime), 0, 10);
                                                                                } ?>" />
                            </div>
                        </td>
                        <td rowspan="3">
                            <strong><?php _e('Description', 'sermon-browser') ?></strong>
                            <div>
                                <?php   if (isset($id3_tags['description'])) {
                                    $desc = $id3_tags['description'];
                                } elseif (isset($curSermon->description)) {
                                    $desc = stripslashes($curSermon->description);
                                } else {
                                    $desc = '';
                                } ?>
                                <textarea name="description" cols="50" rows="7"><?php echo $desc; ?></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong><?php _e('Service', 'sermon-browser') ?></strong><br/>
                            <select id="service" name="service" onchange="createNewService(this)">
                                <?php if (count($services) == 0) : ?>
                                    <option value="" selected="selected"></option>
                                <?php else : ?>
                                    <?php foreach ($services as $service) : ?>
                                        <option value="<?php echo $service->id ?>" <?php echo (isset($curSermon->service_id) && $service->id == $curSermon->service_id) ? 'selected="selected"' : '' ?>><?php echo htmlspecialchars(stripslashes($service->name), ENT_QUOTES) ?></option>
                                    <?php endforeach ?>
                                <?php endif ?>
                                <option value="newService"><?php _e('Create new service', 'sermon-browser') ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <strong><?php _e('Time', 'sermon-browser') ?></strong>
                            <div>
                                <input type="text" name="time" value="<?php echo isset($curSermon->time) ? $curSermon->time : ''?>" id="time" <?php echo isset($curSermon->override) && $curSermon->override ? '' : 'disabled="disabled" class="gray"' ?> />
                                <input type="checkbox" name="override" id="override" onchange="doOverride()" <?php echo isset($curSermon->override) && $curSermon->override ? 'checked="checked"' : ''?>> <?php _e('Override default time', 'sermon-browser') ?>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <strong><?php _e('Bible passage', 'sermon-browser') ?></strong> (<a href="javascript:addPassage()"><?php _e('add more', 'sermon-browser') ?></a>)
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('From', 'sermon-browser') ?></td>
                        <td><?php _e('To', 'sermon-browser') ?></td>
                    </tr>
                    <tr id="passage" class="passage">
                        <td>
                            <table>
                                <tr>
                                    <td>
                                        <select id="startbook" name="start[book][]" onchange="syncBook(this)" class="start1">
                                            <option value=""></option>
                                            <?php foreach ($books as $book) : ?>
                                                <option value="<?php echo $book ?>"><?php echo $translated_books[$book] ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </td>
                                    <td><input type="text" style="width:60px;" name="start[chapter][]" value="" class="start2" /><br /></td>
                                    <td><input type="text" style="width:60px;" name="start[verse][]" value="" class="start3" /><br /></td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table>
                                <tr>
                                    <td>
                                        <select id="endbook" name="end[book][]" class="end">
                                            <option value=""></option>
                                            <?php foreach ($books as $book) : ?>
                                                <option value="<?php echo $book ?>"><?php echo $translated_books[$book] ?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </td>
                                    <td><input type="text" style="width:60px;" name="end[chapter][]" value="" class="end2" /><br /></td>
                                    <td><input type="text" style="width:60px;" name="end[verse][]" value="" class="end3" /><br /></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <strong><?php _e('Attachments', 'sermon-browser') ?></strong> (<a href="javascript:addFile()"><?php _e('add more', 'sermon-browser') ?></a>)
                        </td>
                    </tr>
                    <tr >
                        <td colspan="2">
                            <table>
                                <tr id="choosefile" class="choose">
                                    <th scope="row" style="padding:3px 7px">
                                    <select class="choosefile" name="choosefile" onchange="chooseType(this.name, this.value);">
                                    <option value="filelist"><?php _e('Choose existing file:', 'sermon-browser') ?></option>
                                    <option value="newupload"><?php _e('Upload a new one:', 'sermon-browser') ?></option>
                                    <option value="newurl"><?php _e('Enter an URL:', 'sermon-browser') ?></option>
                                    <option value="newcode"><?php _e('Enter embed or shortcode:', 'sermon-browser') ?></option>
                                    </select>
                                    </th>
                                    <td class="filelist">
                                        <select id="file" name="file[]">
                                        <?php echo count($files) == 0 ? '<option value="0">No files found</option>' : '<option value="0"></option>' ?>
                                        <?php foreach ($files as $file) : ?>
                                            <option value="<?php echo $file->id ?>"><?php echo $file->name ?></option>
                                        <?php endforeach ?>
                                        </select>
                                    </td>
                                    <td class="newupload" style="display:none"><input type="file" size="50" name="upload[]"/></td>
                                    <td class="newurl" style="display:none"><input type="text" size="50" name="url[]"/></td>
                                    <td class="newcode" style="display:none"><input type="text" size="92" name="code[]"/></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </fieldset>
            <p class="submit"><input type="submit" name="save" value="<?php _e('Save', 'sermon-browser') ?> &raquo;" /></p>
            <?php wp_nonce_field('sermon_browser_save', 'sermon_browser_save_nonce'); ?>
            </form>
        </div>
        <script type="text/javascript">
            jQuery.datePicker.setDateFormat('ymd','-');
            jQuery('#date').datePicker({startDate:'01/01/1970'});
            <?php if (empty($curSermon->time)) : ?>
                jQuery('#time').val(timeArr[jQuery('*[selected]', jQuery("select[name='service']")).attr('value')]);
            <?php endif ?>
            <?php if ($mid !== null || (isset($_GET['getid3']))) : ?>
                stuff = new Array();
                type = new Array();
                start1 = new Array();
                start2 = new Array();
                start3 = new Array();
                end1 = new Array();
                end2 = new Array();
                end3 = new Array();

                <?php
                if ($mid !== null) {
                    $assocFiles = File::findBySermonAndType($mid, 'file');
                    $assocURLs = File::findBySermonAndType($mid, 'url');
                    $assocCode = File::findBySermonAndType($mid, 'code');
                } else {
                    $assocFiles = $assocURLs = $assocCode = [];
                }
                    $r = false;
                if (isset($_GET['getid3'])) {
                    $file_data = File::find((int) $_GET['getid3']);
                    if ($file_data !== null) {
                        if ($file_data->type === 'url') {
                            $assocURLs[] = $file_data;
                        } else {
                            $newFile = new \stdClass();
                            $newFile->id = esc_js($_GET['getid3']);
                            $assocFiles[] = $newFile;
                        }
                    }
                }
                ?>

                <?php for ($lolz = 0; $lolz < count($assocFiles); $lolz++) : ?>
                    <?php $r = true ?>
                    addFile();
                    stuff.push(<?php echo $assocFiles[$lolz]->id ?>);
                    type.push('file');
                <?php endfor ?>

                <?php for ($lolz = 0; $lolz < count($assocURLs); $lolz++) : ?>
                    <?php $r = true ?>
                    addFile();
                    stuff.push('<?php echo $assocURLs[$lolz]->name ?>');
                    type.push('url');
                <?php endfor ?>

                <?php for ($lolz = 0; $lolz < count($assocCode); $lolz++) : ?>
                    <?php $r = true ?>
                    addFile();
                    stuff.push('<?php echo $assocCode[$lolz]->name ?>');
                    type.push('code');
                <?php endfor ?>

                <?php if ($r) : ?>
                jQuery('.choose:last').remove();
                <?php endif ?>

                <?php for ($lolz = 0; $lolz < count($startArr); $lolz++) : ?>
                    <?php if ($lolz != 0) : ?>
                        addPassage();
                    <?php endif ?>
                    start1.push("<?php echo $startArr[$lolz]['book'] ?>");
                    start2.push("<?php echo $startArr[$lolz]['chapter'] ?>");
                    start3.push("<?php echo $startArr[$lolz]['verse'] ?>");
                    end1.push("<?php echo $endArr[$lolz]['book'] ?>");
                    end2.push("<?php echo $endArr[$lolz]['chapter'] ?>");
                    end3.push("<?php echo $endArr[$lolz]['verse'] ?>");
                <?php endfor ?>

                jQuery('.choose').each(function(i) {
                    switch (type[i]) {
                        case 'file':
                            jQuery("option[value='filelist']", this).prop('selected', true);
                            jQuery('.filelist', this).css('display','');
                            jQuery("option[value='" + stuff[i] + "']", this).prop('selected', true);
                            break;
                        case 'url':
                            jQuery('td', this).css('display', 'none');
                            jQuery("option[value='newurl']", this).prop('selected', true);
                            jQuery('.newurl ', this).css('display','');
                            jQuery(".newurl input", this).val(stuff[i]);
                            break;
                        case 'code':
                            jQuery('td', this).css('display', 'none');
                            jQuery("option[value='newcode']", this).prop('selected', true);
                            jQuery('.newcode', this).css('display','');
                            jQuery(".newcode input", this).val(Base64.decode(stuff[i]));
                            break;
                    }
                });

                jQuery('.start1').each(function(i) {
                    jQuery("option[value='" + start1[i] + "']", this).prop('selected', true);
                });

                jQuery('.end').each(function(i) {
                    jQuery("option[value='" + end1[i] + "']", this).prop('selected', true);
                });

                jQuery('.start2').each(function(i) {
                    jQuery(this).val(start2[i]);
                });

                jQuery('.start3').each(function(i) {
                    jQuery(this).val(start3[i]);
                });

                jQuery('.end2').each(function(i) {
                    jQuery(this).val(end2[i]);
                });

                jQuery('.end3').each(function(i) {
                    jQuery(this).val(end3[i]);
                });
            <?php endif ?>
        </script>
        <?php
    }
}
