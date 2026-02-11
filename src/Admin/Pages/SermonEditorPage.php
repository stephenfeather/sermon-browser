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

use SermonBrowser\Admin\Pages\SermonId3Importer;
use SermonBrowser\Constants;
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
        $this->handlePost();

        // Handle ID3 tag import.
        $id3Importer = new SermonId3Importer();
        $id3_tags = $id3Importer->import();

        // Load data for the form.
        $formData = $this->loadFormData();

        // Render the form.
        $this->renderForm($formData, $id3_tags, $translated_books);
    }

    /**
     * Handle POST submission.
     *
     * @return bool Whether an error occurred.
     */
    private function handlePost(): bool
    {
        if (!isset($_POST['save']) || !isset($_POST['title'])) {
            return false;
        }

        $this->verifyNonce();

        // Collect form data.
        $formInput = $this->collectFormInput();

        // Process Bible passages.
        [$startz, $endz] = $this->processBiblePassages();
        $formInput['start'] = serialize($startz);
        $formInput['end'] = serialize($endz);

        // Process date with service time.
        $formInput['date'] = $this->processSermonDate($formInput['service_id'], $formInput['override']);

        // Filter description based on user capabilities.
        $formInput['description'] = $this->filterDescription($formInput['description']);

        // Insert or update sermon.
        $id = $this->saveSermon(
            $formInput['title'],
            $formInput['preacher_id'],
            $formInput['service_id'],
            $formInput['series_id'],
            $formInput['date'],
            $formInput['start'],
            $formInput['end'],
            $formInput['description'],
            $formInput['time'],
            $formInput['override']
        );

        // Save related data.
        $this->saveBiblePassages($id, $startz, $endz);
        $error = $this->saveAttachments($id);
        $this->saveTags($id);

        // Redirect on success.
        if (!$error) {
            echo "<script>document.location = '" . admin_url(Constants::SERMON_PAGE . '&saved=true') . "';</script>";
            die();
        }

        return $error;
    }

    /**
     * Verify the nonce for sermon save.
     *
     * @return void
     */
    private function verifyNonce(): void
    {
        if (!wp_verify_nonce($_REQUEST['sermon_browser_save_nonce'] ?? '', 'sermon_browser_save')) {
            wp_die(__("You do not have the correct permissions to edit or create sermons", 'sermon-browser'));
        }
    }

    /**
     * Collect basic form input data.
     *
     * @return array<string, mixed> Form input data.
     */
    private function collectFormInput(): array
    {
        return [
            'title' => sanitize_text_field($_POST['title']),
            'preacher_id' => (int) $_POST['preacher'],
            'service_id' => (int) $_POST['service'],
            'series_id' => (int) $_POST['series'],
            'time' => isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '',
            'override' => (isset($_POST['override']) && $_POST['override'] === 'on') ? 1 : 0,
            'description' => $_POST['description'] ?? '',
        ];
    }

    /**
     * Process Bible passages from POST data.
     *
     * @return array{0: array<int, array{book: string, chapter: int, verse: int}>, 1: array<int, array{book: string, chapter: int, verse: int}>}
     */
    private function processBiblePassages(): array
    {
        $startz = [];
        $endz = [];
        $startBooks = $_POST['start']['book'] ?? [];

        for ($i = 0; $i < count($startBooks); $i++) {
            if (!$this->isValidPassage($i)) {
                continue;
            }
            $startz[] = [
                'book' => sanitize_text_field($_POST['start']['book'][$i]),
                'chapter' => (int) $_POST['start']['chapter'][$i],
                'verse' => (int) $_POST['start']['verse'][$i],
            ];
            $endz[] = [
                'book' => sanitize_text_field($_POST['end']['book'][$i]),
                'chapter' => (int) $_POST['end']['chapter'][$i],
                'verse' => (int) $_POST['end']['verse'][$i],
            ];
        }

        return [$startz, $endz];
    }

    /**
     * Check if a passage at the given index has all required fields.
     *
     * @param int $index Passage index.
     * @return bool True if valid.
     */
    private function isValidPassage(int $index): bool
    {
        return !empty($_POST['start']['chapter'][$index])
            && !empty($_POST['end']['chapter'][$index])
            && !empty($_POST['start']['verse'][$index])
            && !empty($_POST['end']['verse'][$index]);
    }

    /**
     * Process the sermon date with optional service time.
     *
     * @param int $serviceId Service ID.
     * @param int $override Whether to override service time.
     * @return string Formatted date string.
     */
    private function processSermonDate(int $serviceId, int $override): string
    {
        $date = strtotime($_POST['date']);

        if (!$date) {
            return '1970-01-01 00:00';
        }

        $timeOffset = $this->getTimeOffset($serviceId, $override);
        $date = $date - strtotime('00:00') + $timeOffset;

        return date('Y-m-d H:i:s', $date);
    }

    /**
     * Get the time offset for a sermon date.
     *
     * @param int $serviceId Service ID.
     * @param int $override Whether to override service time.
     * @return int Time offset in seconds.
     */
    private function getTimeOffset(int $serviceId, int $override): int
    {
        if ($override) {
            return strtotime($_POST['time']);
        }

        $service = Service::find($serviceId);
        $serviceTime = $service ? $service->time : null;

        return $serviceTime ? strtotime($serviceTime) : strtotime('00:00');
    }

    /**
     * Filter description based on user HTML capabilities.
     *
     * @param string $description Raw description.
     * @return string Filtered description.
     */
    private function filterDescription(string $description): string
    {
        if (current_user_can('unfiltered_html')) {
            return $description;
        }

        global $allowedposttags;
        return wp_kses($description, $allowedposttags);
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
            $startArr = unserialize($curSermon->start, ['allowed_classes' => false]) ?: [];
            $endArr = unserialize($curSermon->end, ['allowed_classes' => false]) ?: [];

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
     * @return void
     */
    private function renderForm(array $formData, array $id3_tags, array $translated_books): void
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

        // Pre-compute form values using helpers.
        $titleValue = $this->getFormValue($id3_tags, $curSermon, 'title', 'title');
        $titleValue = is_string($titleValue) ? htmlspecialchars(stripslashes($titleValue)) : '';
        $tagsValue = isset($tags) ? stripslashes($tags) : '';
        $preacherId = $this->getSelectedId($id3_tags, $curSermon, 'preacher', 'preacher_id');
        $seriesId = $this->getSelectedId($id3_tags, $curSermon, 'series', 'series_id');
        $serviceId = $curSermon->service_id ?? -1;
        $dateValue = $this->getDateValue($id3_tags, $curSermon);
        $descValue = $this->getFormValue($id3_tags, $curSermon, 'description', 'description');
        $descValue = is_string($descValue) ? stripslashes($descValue) : '';
        $timeValue = $curSermon->time ?? '';
        $overrideChecked = isset($curSermon->override) && $curSermon->override;

        // Render form components.
        $this->renderFormHelperScripts($timeArr);
        sb_do_alerts();
        $this->renderFormHeader($mid);
        $this->renderImportPrompt($mid);
        ?>
            <br/>
            <form method="post" enctype="multipart/form-data">
            <fieldset>
                <div class="widefat" style="padding: 1em;">
                    <div style="margin-bottom: 1em;"><strong><?php _e('Enter sermon details', 'sermon-browser') ?></strong></div>

                    <!-- Title | Tags row -->
                    <div style="display: flex; gap: 1em; margin-bottom: 1em;">
                        <div style="flex: 1;">
                            <strong><?php _e('Title', 'sermon-browser') ?></strong>
                            <div>
                                <input type="text" value="<?php echo esc_attr($titleValue); ?>" name="title" size="60" style="width:400px;" />
                            </div>
                        </div>
                        <div style="flex: 1;">
                            <strong><?php _e('Tags (comma separated)', 'sermon-browser') ?></strong>
                            <div>
                                <input type="text" name="tags" value="<?php echo esc_attr($tagsValue); ?>" style="width:400px" />
                            </div>
                        </div>
                    </div>

                    <!-- Preacher | Series row -->
                    <div style="display: flex; gap: 1em; margin-bottom: 1em;">
                        <div style="flex: 1;">
                            <strong><?php _e('Preacher', 'sermon-browser') ?></strong><br/>
                            <?php $this->renderSelectDropdown('preacher', 'preacher', $preachers, $preacherId, __('Create new preacher', 'sermon-browser'), 'createNewPreacher(this)'); ?>
                        </div>
                        <div style="flex: 1;">
                            <strong><?php _e('Series', 'sermon-browser') ?></strong><br/>
                            <?php $this->renderSelectDropdown('series', 'series', $series, $seriesId, __('Create new series', 'sermon-browser'), 'createNewSeries(this)'); ?>
                        </div>
                    </div>

                    <!-- Date/Service/Time | Description grid (Description spans 3 logical rows) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1em; margin-bottom: 1em;">
                        <div>
                            <div style="margin-bottom: 1em; overflow: visible;">
                                <strong><?php _e('Date', 'sermon-browser') ?></strong> (yyyy-mm-dd)
                                <div>
                                    <input type="text" id="date" name="date" value="<?php echo esc_attr($dateValue); ?>" />
                                </div>
                            </div>
                            <div style="margin-bottom: 1em;">
                                <strong><?php _e('Service', 'sermon-browser') ?></strong><br/>
                                <?php $this->renderSelectDropdown('service', 'service', $services, $serviceId, __('Create new service', 'sermon-browser'), 'createNewService(this)'); ?>
                            </div>
                            <div>
                                <strong><?php _e('Time', 'sermon-browser') ?></strong>
                                <div>
                                    <input type="text" name="time" value="<?php echo esc_attr($timeValue); ?>" id="time" <?php echo $overrideChecked ? '' : 'disabled="disabled" class="gray"'; ?> />
                                    <input type="checkbox" name="override" id="override" onchange="doOverride()" <?php echo $overrideChecked ? 'checked="checked"' : ''; ?>> <?php _e('Override default time', 'sermon-browser') ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <strong><?php _e('Description', 'sermon-browser') ?></strong>
                            <div>
                                <textarea name="description" cols="50" rows="7"><?php echo esc_textarea($descValue); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Bible passage section -->
                    <div style="margin-bottom: 0.5em;">
                        <strong><?php _e('Bible passage', 'sermon-browser') ?></strong> (<a href="javascript:addPassage()"><?php _e('add more', 'sermon-browser') ?></a>)
                    </div>
                    <div style="display: flex; gap: 1em; margin-bottom: 0.5em;">
                        <div style="flex: 1;"><?php _e('From', 'sermon-browser') ?></div>
                        <div style="flex: 1;"><?php _e('To', 'sermon-browser') ?></div>
                    </div>
                    <div id="passage" class="passage" style="display: flex; gap: 1em; margin-bottom: 1em;">
                        <div style="flex: 1; display: flex; gap: 0.5em; align-items: center;">
                            <?php $this->renderBooksDropdown('startbook', 'start[book][]', $books, $translated_books, 'start1', 'syncBook(this)'); ?>
                            <input type="text" style="width:60px;" name="start[chapter][]" value="" class="start2" />
                            <input type="text" style="width:60px;" name="start[verse][]" value="" class="start3" />
                        </div>
                        <div style="flex: 1; display: flex; gap: 0.5em; align-items: center;">
                            <?php $this->renderBooksDropdown('endbook', 'end[book][]', $books, $translated_books, 'end'); ?>
                            <input type="text" style="width:60px;" name="end[chapter][]" value="" class="end2" />
                            <input type="text" style="width:60px;" name="end[verse][]" value="" class="end3" />
                        </div>
                    </div>

                    <?php $this->renderAttachmentsSection($files); ?>
                </div>
            </fieldset>
            <p class="submit"><input type="submit" name="save" value="<?php _e('Save', 'sermon-browser') ?> &raquo;" /></p>
            <?php wp_nonce_field('sermon_browser_save', 'sermon_browser_save_nonce'); ?>
            </form>
        </div>
        <?php
        $this->renderFormInitScripts($curSermon, $mid, $startArr, $endArr);
    }

    /**
     * Get the date value for the form.
     *
     * @param array<string, mixed> $id3_tags ID3 tag data.
     * @param object|null $curSermon Current sermon object.
     * @return string The date value.
     */
    private function getDateValue(array $id3_tags, ?object $curSermon): string
    {
        if (isset($id3_tags['date'])) {
            return $id3_tags['date'];
        }
        if ($curSermon !== null && isset($curSermon->datetime) && $curSermon->datetime !== '1970-01-01 00:00:00') {
            return substr(stripslashes($curSermon->datetime), 0, 10);
        }
        return '';
    }

    /**
     * Render the form header with logo and title.
     *
     * @param int|null $mid Sermon ID if editing.
     * @return void
     */
    private function renderFormHeader(?int $mid): void
    {
        $pageTitle = $mid !== null ? __('Edit Sermon', 'sermon-browser') : __('Add Sermon', 'sermon-browser');
        ?>
        <div class="wrap">
            <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" alt="<?php esc_attr_e('Sermon Browser logo', 'sermon-browser'); ?>" /></a>
            <h2><?php echo esc_html($pageTitle); ?></h2>
        <?php
    }

    /**
     * Render the import prompt if applicable.
     *
     * @param int|null $mid Sermon ID if editing.
     * @return void
     */
    private function renderImportPrompt(?int $mid): void
    {
        if ($mid !== null || isset($_GET['getid3']) || !sb_get_option('import_prompt')) {
            return;
        }

        if (!sb_import_options_set()) {
            echo '<p class="plugin-update">';
            sb_print_import_options_message(true);
            echo "</p>\n";
        } else {
            sb_print_upload_form();
        }
    }

    /**
     * Render the attachments section.
     *
     * @param array<object> $files Available files.
     * @return void
     */
    private function renderAttachmentsSection(array $files): void
    {
        ?>
                    <!-- Attachments section -->
                    <div style="margin-bottom: 0.5em;">
                        <strong><?php _e('Attachments', 'sermon-browser') ?></strong> (<a href="javascript:addFile()"><?php _e('add more', 'sermon-browser') ?></a>)
                    </div>
                    <div>
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
                                    <?php echo empty($files) ? '<option value="0">No files found</option>' : '<option value="0"></option>'; ?>
                                    <?php foreach ($files as $file) : ?>
                                        <option value="<?php echo esc_attr((string) $file->id); ?>"><?php echo esc_html($file->name); ?></option>
                                    <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="newupload" style="display:none"><input type="file" size="50" name="upload[]"/></td>
                                <td class="newurl" style="display:none"><input type="text" size="50" name="url[]"/></td>
                                <td class="newcode" style="display:none"><input type="text" size="92" name="code[]"/></td>
                            </tr>
                        </table>
                    </div>
        <?php
    }

    /**
     * Render the JavaScript helper functions for the form.
     *
     * @param string $timeArr JavaScript array initialization code for service times.
     * @return void
     */
    private function renderFormHelperScripts(string $timeArr): void
    {
        ?>
        <script type="text/javascript">
            var timeArr = new Array();
            <?php echo $timeArr ?>
            function createNewPreacher(s) {
                if (jQuery('#preacher')[0].value != 'newPreacher') return;
                var p = prompt("<?php _e("New preacher's name?", 'sermon-browser')?>", "<?php _e("Preacher's name", 'sermon-browser')?>");
                if (p != null) {
                    jQuery.post('<?php echo admin_url(Constants::SERMON_PAGE); ?>', {pname: p, sermon: 1}, function(r) {
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
                    jQuery.post('<?php echo admin_url(Constants::SERMON_PAGE); ?>', {sname: s, sermon: 1}, function(r) {
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
                    jQuery.post('<?php echo admin_url(Constants::SERMON_PAGE); ?>', {ssname: ss, sermon: 1}, function(r) {
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
        <?php
    }

    /**
     * Render the form initialization JavaScript.
     *
     * Handles datepicker setup and populating existing sermon data.
     *
     * @param object|null $curSermon Current sermon being edited.
     * @param int|null $mid Sermon ID being edited.
     * @param array $startArr Bible passage start data.
     * @param array $endArr Bible passage end data.
     * @return void
     */
    private function renderFormInitScripts(?object $curSermon, ?int $mid, array $startArr, array $endArr): void
    {
        ?>
        <script type="text/javascript">
            jQuery.datePicker.setDateFormat('ymd','-');
            jQuery('#date').datePicker({startDate:'01/01/1970'});
            <?php if (empty($curSermon->time)) : ?>
                jQuery('#time').val(timeArr[jQuery('*[selected]', jQuery("select[name='service']")).attr('value')]);
            <?php endif ?>
            <?php if ($mid !== null || (isset($_GET['getid3']))) : ?>
                <?php $this->renderExistingDataInit($mid); ?>
                <?php $this->renderPassageDataInit($startArr, $endArr); ?>
                <?php $this->renderFileAndPassageSelectors(); ?>
            <?php endif ?>
        </script>
        <?php
    }

    /**
     * Render JavaScript to initialize existing file data.
     *
     * @param int|null $mid Sermon ID being edited.
     * @return void
     */
    private function renderExistingDataInit(?int $mid): void
    {
        $fileData = $this->loadAssociatedFiles($mid);
        ?>
                stuff = new Array();
                type = new Array();
                start1 = new Array();
                start2 = new Array();
                start3 = new Array();
                end1 = new Array();
                end2 = new Array();
                end3 = new Array();

                <?php
                $hasFiles = false;
                $hasFiles = $this->renderFileArrayInit($fileData['files'], 'file', $hasFiles);
                $hasFiles = $this->renderFileArrayInit($fileData['urls'], 'url', $hasFiles);
                $hasFiles = $this->renderFileArrayInit($fileData['code'], 'code', $hasFiles);
                ?>

                <?php if ($hasFiles) : ?>
                jQuery('.choose:last').remove();
                <?php endif ?>
        <?php
    }

    /**
     * Load associated files for a sermon.
     *
     * @param int|null $mid Sermon ID.
     * @return array{files: array<object>, urls: array<object>, code: array<object>}
     */
    private function loadAssociatedFiles(?int $mid): array
    {
        $assocFiles = $mid !== null ? File::findBySermonAndType($mid, 'file') : [];
        $assocURLs = $mid !== null ? File::findBySermonAndType($mid, 'url') : [];
        $assocCode = $mid !== null ? File::findBySermonAndType($mid, 'code') : [];

        // Handle ID3 import.
        if (isset($_GET['getid3'])) {
            $this->addId3File($assocFiles, $assocURLs);
        }

        return ['files' => $assocFiles, 'urls' => $assocURLs, 'code' => $assocCode];
    }

    /**
     * Add file from ID3 import to appropriate array.
     *
     * @param array<object> $assocFiles Files array (modified by reference).
     * @param array<object> $assocURLs URLs array (modified by reference).
     * @return void
     */
    private function addId3File(array &$assocFiles, array &$assocURLs): void
    {
        $file_data = File::find((int) $_GET['getid3']);
        if ($file_data === null) {
            return;
        }

        if ($file_data->type === 'url') {
            $assocURLs[] = $file_data;
        } else {
            $newFile = new \stdClass();
            $newFile->id = esc_js($_GET['getid3']);
            $assocFiles[] = $newFile;
        }
    }

    /**
     * Render JavaScript array initialization for files.
     *
     * @param array<object> $files Files to render.
     * @param string $type File type ('file', 'url', 'code').
     * @param bool $hasFiles Whether files have been rendered.
     * @return bool Updated hasFiles flag.
     */
    private function renderFileArrayInit(array $files, string $type, bool $hasFiles): bool
    {
        foreach ($files as $file) {
            $hasFiles = true;
            $value = $type === 'file' ? $file->id : "'{$file->name}'";
            ?>
                    addFile();
                    stuff.push(<?php echo $value ?>);
                    type.push('<?php echo $type ?>');
            <?php
        }
        return $hasFiles;
    }

    /**
     * Render JavaScript to initialize Bible passage data.
     *
     * @param array $startArr Bible passage start data.
     * @param array $endArr Bible passage end data.
     * @return void
     */
    private function renderPassageDataInit(array $startArr, array $endArr): void
    {
        for ($i = 0; $i < count($startArr); $i++) :
            if ($i != 0) : ?>
                        addPassage();
            <?php endif ?>
                    start1.push("<?php echo esc_js($startArr[$i]['book']); ?>");
                    start2.push("<?php echo esc_js($startArr[$i]['chapter']); ?>");
                    start3.push("<?php echo esc_js($startArr[$i]['verse']); ?>");
                    end1.push("<?php echo esc_js($endArr[$i]['book']); ?>");
                    end2.push("<?php echo esc_js($endArr[$i]['chapter']); ?>");
                    end3.push("<?php echo esc_js($endArr[$i]['verse']); ?>");
        <?php endfor;
    }

    /**
     * Render JavaScript selectors to populate file and passage UI.
     *
     * @return void
     */
    private function renderFileAndPassageSelectors(): void
    {
        ?>
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
        <?php
    }

    /**
     * Get a form field value with priority: id3_tags > curSermon > default.
     *
     * @param array<string, mixed> $id3_tags ID3 tag data.
     * @param object|null $curSermon Current sermon object.
     * @param string $id3Key Key in id3_tags array.
     * @param string $sermonProp Property on sermon object.
     * @param mixed $default Default value.
     * @return mixed The resolved value.
     */
    private function getFormValue(array $id3_tags, ?object $curSermon, string $id3Key, string $sermonProp, mixed $default = ''): mixed
    {
        if (isset($id3_tags[$id3Key])) {
            return $id3_tags[$id3Key];
        }
        if ($curSermon !== null && isset($curSermon->$sermonProp)) {
            return $curSermon->$sermonProp;
        }
        return $default;
    }

    /**
     * Get the selected ID for a dropdown with priority: id3_tags > curSermon > default.
     *
     * @param array<string, mixed> $id3_tags ID3 tag data.
     * @param object|null $curSermon Current sermon object.
     * @param string $id3Key Key in id3_tags array.
     * @param string $sermonProp Property on sermon object.
     * @return int The selected ID or -1 if none.
     */
    private function getSelectedId(array $id3_tags, ?object $curSermon, string $id3Key, string $sermonProp): int
    {
        if (isset($id3_tags[$id3Key])) {
            return (int) $id3_tags[$id3Key];
        }
        if ($curSermon !== null && isset($curSermon->$sermonProp)) {
            return (int) $curSermon->$sermonProp;
        }
        return -1;
    }

    /**
     * Render a select dropdown with options.
     *
     * @param string $id Element ID.
     * @param string $name Element name.
     * @param array<object> $items Items with id and name properties.
     * @param int $selectedId Currently selected ID.
     * @param string $createLabel Label for "create new" option.
     * @param string $onChange JavaScript onChange handler.
     * @return void
     */
    private function renderSelectDropdown(
        string $id,
        string $name,
        array $items,
        int $selectedId,
        string $createLabel,
        string $onChange = ''
    ): void {
        $onChangeAttr = $onChange ? ' onchange="' . esc_attr($onChange) . '"' : '';
        echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"' . $onChangeAttr . '>';

        if (empty($items)) {
            echo '<option value="" selected="selected"></option>';
        } else {
            foreach ($items as $item) {
                $selected = ($item->id === $selectedId) ? ' selected="selected"' : '';
                $itemName = htmlspecialchars(stripslashes($item->name), ENT_QUOTES);
                echo '<option value="' . esc_attr((string) $item->id) . '"' . $selected . '>' . $itemName . '</option>';
            }
        }

        echo '<option value="new' . ucfirst($id) . '">' . esc_html($createLabel) . '</option>';
        echo '</select>';
    }

    /**
     * Render the books dropdown for Bible passage selection.
     *
     * @param string $id Element ID.
     * @param string $name Element name.
     * @param array<string> $books Book identifiers.
     * @param array<string, string> $translatedBooks Translated book names.
     * @param string $class CSS class.
     * @param string $onChange JavaScript onChange handler.
     * @return void
     */
    private function renderBooksDropdown(
        string $id,
        string $name,
        array $books,
        array $translatedBooks,
        string $class = '',
        string $onChange = ''
    ): void {
        $classAttr = $class ? ' class="' . esc_attr($class) . '"' : '';
        $onChangeAttr = $onChange ? ' onchange="' . esc_attr($onChange) . '"' : '';
        echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"' . $classAttr . $onChangeAttr . '>';
        echo '<option value=""></option>';

        foreach ($books as $book) {
            $translatedName = $translatedBooks[$book] ?? $book;
            echo '<option value="' . esc_attr($book) . '">' . esc_html($translatedName) . '</option>';
        }

        echo '</select>';
    }
}
