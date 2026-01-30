<?php
/**
 * Preachers Page.
 *
 * Handles the Preachers admin page for managing preacher records.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

/**
 * Class PreachersPage
 *
 * Manages preacher CRUD operations including image uploads.
 */
class PreachersPage
{
    /**
     * WordPress database instance.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Upload directory path.
     *
     * @var string
     */
    private string $uploadDir;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->uploadDir = sb_get_option('upload_dir');
    }

    /**
     * Render the preachers page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!current_user_can('manage_categories')) {
            wp_die(__("You do not have the correct permissions to manage the preachers' database", 'sermon-browser'));
        }

        // Display saved message.
        if (isset($_GET['saved'])) {
            echo '<div id="message" class="updated fade"><p><b>' .
                __('Preacher saved to database.', 'sermon-browser') . '</b></div>';
        }

        // Handle form submissions.
        $this->handlePost();

        // Handle delete action.
        $this->handleDelete();

        // Route to appropriate view.
        if (isset($_GET['act']) && ($_GET['act'] === 'new' || $_GET['act'] === 'edit')) {
            $this->renderEditForm();
            return;
        }

        // Display alerts and list view.
        sb_do_alerts();
        $this->renderListView();
    }

    /**
     * Handle POST submissions (save preacher).
     *
     * @return void
     */
    private function handlePost(): void
    {
        if (!isset($_POST['save'])) {
            return;
        }

        // Verify nonce.
        if (!isset($_POST['sermon_manage_preachers_nonce']) ||
            !wp_verify_nonce($_POST['sermon_manage_preachers_nonce'], 'sermon_manage_preachers')) {
            wp_die(__("You do not have the correct permissions to manage the preachers database", 'sermon-browser'));
        }

        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $error = false;
        $pid = (int) $_REQUEST['pid'];

        // Handle image upload.
        $filename = $this->handleImageUpload($pid, $error);

        // Handle remove checkbox.
        if (isset($_POST['remove'])) {
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}sb_preachers SET name = %s, description = %s, image = '' WHERE id = %d",
                $name,
                $description,
                $pid
            ));
            @unlink(SB_ABSPATH . sb_get_option('upload_dir') . 'images/' . sanitize_file_name($_POST['old']));
        } elseif ($pid === 0) {
            // Insert new preacher.
            $this->wpdb->query($this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}sb_preachers VALUES (null, %s, %s, %s)",
                $name,
                $description,
                $filename
            ));
        } else {
            // Update existing preacher.
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}sb_preachers SET name = %s, description = %s, image = %s WHERE id = %d",
                $name,
                $description,
                $filename,
                $pid
            ));

            // Delete old image if changed.
            if ($_POST['old'] !== $filename) {
                @unlink(SB_ABSPATH . sb_get_option('upload_dir') . 'images/' . sanitize_file_name($_POST['old']));
            }
        }

        if (!$error) {
            echo "<script>document.location = '" .
                admin_url('admin.php?page=sermon-browser/preachers.php&saved=true') . "';</script>";
        }
    }

    /**
     * Handle image upload for preacher.
     *
     * @param int  $pid   Preacher ID.
     * @param bool $error Error flag (passed by reference).
     * @return string Filename of uploaded image.
     */
    private function handleImageUpload(int $pid, bool &$error): string
    {
        if (empty($_FILES['upload']['name'])) {
            // No new upload - get existing image.
            $p = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT image FROM {$this->wpdb->prefix}sb_preachers WHERE id = %d",
                    $pid
                )
            );
            return $p ? $p->image : '';
        }

        if ($_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
            $error = true;
            echo '<div id="message" class="updated fade"><p><b>' .
                __('Could not upload file. Please check the Options page for any errors or warnings.', 'sermon-browser') .
                '</b></div>';
            return '';
        }

        // Process upload.
        $filename = basename($_FILES['upload']['name']);
        $imagesDir = SB_ABSPATH . $this->uploadDir . 'images';

        // Create images directory if needed.
        if (!is_dir($imagesDir) && sb_mkdir($imagesDir)) {
            @chmod($imagesDir, 0777);
        }

        $dest = $imagesDir . '/' . $filename;

        if (@move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
            return $filename;
        }

        $error = true;
        echo '<div id="message" class="updated fade"><p><b>' .
            __('Could not save uploaded file. Please try again.', 'sermon-browser') . '</b></div>';
        @chmod($imagesDir, 0777);

        return '';
    }

    /**
     * Handle delete action.
     *
     * @return void
     */
    private function handleDelete(): void
    {
        if (!isset($_GET['act']) || $_GET['act'] !== 'kill') {
            return;
        }

        $pid = (int) $_GET['pid'];

        // Check if preacher has sermons.
        $sermonCount = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->wpdb->prefix}sb_sermons WHERE preacher_id = %d",
            $pid
        ));

        if ($sermonCount > 0) {
            echo '<div id="message" class="updated fade"><p><b>' .
                __("You cannot delete this preacher until you first delete any sermons they have preached.", 'sermon-browser') .
                '</b></div>';
            return;
        }

        // Get and delete image.
        $p = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT image FROM {$this->wpdb->prefix}sb_preachers WHERE id = %d",
            $pid
        ));

        if ($p && $p->image) {
            @unlink(SB_ABSPATH . sb_get_option('upload_dir') . 'images/' . $p->image);
        }

        // Delete preacher.
        $this->wpdb->query($this->wpdb->prepare(
            "DELETE FROM {$this->wpdb->prefix}sb_preachers WHERE id = %d",
            $pid
        ));
    }

    /**
     * Render the edit/add form.
     *
     * @return void
     */
    private function renderEditForm(): void
    {
        $preacher = null;
        $isEdit = ($_GET['act'] === 'edit');

        if ($isEdit) {
            $preacher = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}sb_preachers WHERE id = %d",
                (int) $_GET['pid']
            ));
        }

        $pid = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;
        ?>
        <div class="wrap">
            <a href="http://www.sermonbrowser.com/">
                <img src="<?php echo SB_PLUGIN_URL; ?>/sb-includes/logo-small.png"
                     width="191" height="35" style="margin: 1em 2em; float: right;"/>
            </a>
            <h2>
                <?php echo $isEdit ? __('Edit', 'sermon-browser') : __('Add', 'sermon-browser'); ?>
                <?php _e('preacher', 'sermon-browser'); ?>
            </h2>
            <br style="clear:both">

            <?php $this->checkImagesFolder(); ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="pid" value="<?php echo $pid; ?>">
                <fieldset>
                    <table class="widefat">
                        <tr>
                            <td>
                                <strong><?php _e('Name', 'sermon-browser'); ?></strong>
                                <div>
                                    <input type="text"
                                           value="<?php echo isset($preacher->name) ? esc_attr(stripslashes($preacher->name)) : ''; ?>"
                                           name="name" size="60" style="width:400px;"/>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong><?php _e('Description', 'sermon-browser'); ?></strong>
                                <div>
                                    <textarea name="description" cols="100" rows="5"><?php
                                        echo isset($preacher->description) ? esc_textarea(stripslashes($preacher->description)) : '';
                                    ?></textarea>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?php if ($isEdit && $preacher && $preacher->image): ?>
                                    <div>
                                        <img src="<?php echo esc_url(trailingslashit(site_url()) . sb_get_option('upload_dir') . 'images/' . $preacher->image); ?>">
                                    </div>
                                    <input type="hidden" name="old" value="<?php echo esc_attr($preacher->image); ?>">
                                <?php endif; ?>
                                <strong><?php _e('Image', 'sermon-browser'); ?></strong>
                                <div>
                                    <input type="file" name="upload">
                                    <label>
                                        <?php _e('Remove image', 'sermon-browser'); ?>&nbsp;
                                        <input type="checkbox" name="remove" value="true">
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </fieldset>
                <?php wp_nonce_field('sermon_manage_preachers', 'sermon_manage_preachers_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="save" value="<?php _e('Save', 'sermon-browser'); ?> &raquo;"/>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Check and create images folder if needed.
     *
     * @return void
     */
    private function checkImagesFolder(): void
    {
        $imagesDir = SB_ABSPATH . $this->uploadDir . 'images';
        $checkSermonUpload = sb_checkSermonUploadable('images/');

        if ($checkSermonUpload === 'notexist') {
            if (!is_dir($imagesDir) && mkdir($imagesDir)) {
                chmod($imagesDir, 0777);
            }
            $checkSermonUpload = sb_checkSermonUploadable('images/');
        }

        if ($checkSermonUpload !== 'writeable') {
            echo '<div id="message" class="updated fade"><p><b>' .
                __("The images folder is not writeable. You won't be able to upload images.", 'sermon-browser') .
                '</b></div>';
        }
    }

    /**
     * Render the preachers list view.
     *
     * @return void
     */
    private function renderListView(): void
    {
        $preachers = $this->wpdb->get_results(
            "SELECT {$this->wpdb->prefix}sb_preachers.*,
                    COUNT({$this->wpdb->prefix}sb_sermons.id) AS sermon_count
             FROM {$this->wpdb->prefix}sb_preachers
             LEFT JOIN {$this->wpdb->prefix}sb_sermons
                ON {$this->wpdb->prefix}sb_preachers.id = preacher_id
             GROUP BY {$this->wpdb->prefix}sb_preachers.id
             ORDER BY name ASC"
        );
        ?>
        <div class="wrap">
            <a href="http://www.sermonbrowser.com/">
                <img src="<?php echo SB_PLUGIN_URL; ?>/sb-includes/logo-small.png"
                     width="191" height="35" style="margin: 1em 2em; float: right;"/>
            </a>
            <h2>
                <?php _e('Preachers', 'sermon-browser'); ?>
                (<a href="<?php echo admin_url('admin.php?page=sermon-browser/preachers.php&act=new'); ?>">
                    <?php _e('add new', 'sermon-browser'); ?>
                </a>)
            </h2>
            <br/>
            <table class="widefat" style="width:auto">
                <thead>
                <tr>
                    <th scope="col" style="text-align:center"><?php _e('ID', 'sermon-browser'); ?></th>
                    <th scope="col"><?php _e('Name', 'sermon-browser'); ?></th>
                    <th scope="col" style="text-align:center"><?php _e('Image', 'sermon-browser'); ?></th>
                    <th scope="col" style="text-align:center"><?php _e('Sermons', 'sermon-browser'); ?></th>
                    <th scope="col" style="text-align:center"><?php _e('Actions', 'sermon-browser'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                foreach ((array) $preachers as $preacher):
                    $rowClass = (++$i % 2 === 0) ? 'alternate' : '';
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td style="text-align:center"><?php echo (int) $preacher->id; ?></td>
                        <td><?php echo esc_html(stripslashes($preacher->name)); ?></td>
                        <td style="text-align:center">
                            <?php if ($preacher->image): ?>
                                <img src="<?php echo esc_url(trailingslashit(site_url()) . sb_get_option('upload_dir') . 'images/' . $preacher->image); ?>">
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center"><?php echo (int) $preacher->sermon_count; ?></td>
                        <td style="text-align:center">
                            <?php $this->renderActions($preacher, count($preachers)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render action links for a preacher row.
     *
     * @param object $preacher      Preacher object.
     * @param int    $totalPreachers Total number of preachers.
     * @return void
     */
    private function renderActions(object $preacher, int $totalPreachers): void
    {
        $editUrl = admin_url('admin.php?page=sermon-browser/preachers.php&act=edit&pid=' . $preacher->id);
        $deleteUrl = admin_url('admin.php?page=sermon-browser/preachers.php&act=kill&pid=' . $preacher->id);

        echo '<a href="' . esc_url($editUrl) . '">' . __('Edit', 'sermon-browser') . '</a>';

        if ($totalPreachers < 2) {
            // Can't delete the only preacher.
            echo ' | <a href="javascript:alert(\'' .
                esc_js(__('You must have at least one preacher in the database.', 'sermon-browser')) .
                '\')">' . __('Delete', 'sermon-browser') . '</a>';
        } elseif ($preacher->sermon_count != 0) {
            // Can't delete preacher with sermons.
            echo ' | <a href="javascript:alert(\'' .
                esc_js(__('You cannot delete this preacher until you first delete any sermons they have preached.', 'sermon-browser')) .
                '\')">' . __('Delete', 'sermon-browser') . '</a>';
        } else {
            // Can delete.
            $confirmMsg = sprintf(
                __('Are you sure you want to delete %s?', 'sermon-browser'),
                stripslashes($preacher->name)
            );
            echo ' | <a onclick="return confirm(\'' . esc_js($confirmMsg) . '\')" href="' .
                esc_url($deleteUrl) . '">' . __('Delete', 'sermon-browser') . '</a>';
        }
    }
}
