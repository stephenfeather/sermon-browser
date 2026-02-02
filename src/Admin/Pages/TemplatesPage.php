<?php

/**
 * Templates Page.
 *
 * Handles the Templates admin page for editing display templates.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

/**
 * Class TemplatesPage
 *
 * Manages template editing for search results and sermon pages.
 */
class TemplatesPage
{
    /**
     * Render the templates page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!current_user_can('manage_options')) {
            wp_die(__("You do not have the correct permissions to edit the SermonBrowser templates", 'sermon-browser'));
        }

        $this->handleSave();
        sb_do_alerts();
        $this->renderForm();
    }

    /**
     * Handle save or reset POST request.
     *
     * @return void
     */
    private function handleSave(): void
    {
        if (!isset($_POST['save']) && !isset($_POST['resetdefault'])) {
            return;
        }

        if (
            !isset($_POST['sermon_template_edit_nonce']) ||
            !wp_verify_nonce($_POST['sermon_template_edit_nonce'], 'sermon_template_edit')
        ) {
            wp_die(__("You do not have the correct permissions to edit the SermonBrowser templates", 'sermon-browser'));
        }

        $multi = wp_kses_post($_POST['multi']);
        $single = wp_kses_post($_POST['single']);
        $style = wp_kses_post($_POST['style']);

        if (isset($_POST['resetdefault'])) {
            // Note: sb_default_*() wrappers are available from sermon.php.
            $multi = sb_default_multi_template();
            $single = sb_default_single_template();
            $style = sb_default_css();
        }

        sb_update_option('search_template', $multi);
        sb_update_option('single_template', $single);
        sb_update_option('css_style', $style);
        sb_update_option('style_date_modified', strtotime('now'));

        // Clear template cache so changes take effect immediately.
        delete_transient('sb_template_search');
        delete_transient('sb_template_single');

        echo '<div id="message" class="updated fade"><p><b>';
        _e('Templates saved successfully.', 'sermon-browser');
        echo '</b></p></div>';
    }

    /**
     * Render the templates form.
     *
     * @return void
     */
    private function renderForm(): void
    {
        ?>
    <form method="post">
    <div class="wrap">
        <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" alt="<?php esc_attr_e('Sermon Browser logo', 'sermon-browser'); ?>" /></a>
        <h2><?php _e('Templates', 'sermon-browser'); ?></h2>
        <br/>
        <div class="widefat" style="background: #fff; border: 1px solid #c3c4c7; padding: 1em;">
            <div style="display: flex; gap: 1em; margin-bottom: 1em;">
                <label for="multi" style="min-width: 150px; text-align: right; padding-top: 0.5em;"><?php _e('Search results page', 'sermon-browser'); ?>:</label>
                <div style="flex: 1;">
                    <?php sb_build_textarea('multi', sb_get_option('search_template')); ?>
                </div>
            </div>
            <div style="display: flex; gap: 1em; margin-bottom: 1em;">
                <label for="single" style="min-width: 150px; text-align: right; padding-top: 0.5em;"><?php _e('Sermon page', 'sermon-browser'); ?>:</label>
                <div style="flex: 1;">
                    <?php sb_build_textarea('single', sb_get_option('single_template')); ?>
                </div>
            </div>
            <div style="display: flex; gap: 1em;">
                <label for="style" style="min-width: 150px; text-align: right; padding-top: 0.5em;"><?php _e('Style', 'sermon-browser'); ?>:</label>
                <div style="flex: 1;">
                    <?php sb_build_textarea('style', sb_get_option('css_style')); ?>
                </div>
            </div>
        </div>
        <p class="submit"><input type="submit" name="save" value="<?php _e('Save', 'sermon-browser'); ?> &raquo;" /> <input type="submit" name="resetdefault" value="<?php _e('Reset to defaults', 'sermon-browser'); ?>"  /></p>
    </div>
        <?php wp_nonce_field('sermon_template_edit', 'sermon_template_edit_nonce'); ?>
    </form>
    <script>
        jQuery("form").submit(function() {
            var yes = confirm("Are you sure ?");
            if(!yes) return false;
        });
    </script>
        <?php
    }
}
