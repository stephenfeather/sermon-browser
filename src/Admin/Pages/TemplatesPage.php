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

        if (!isset($_POST['sermon_template_edit_nonce']) ||
            !wp_verify_nonce($_POST['sermon_template_edit_nonce'], 'sermon_template_edit')) {
            wp_die(__("You do not have the correct permissions to edit the SermonBrowser templates", 'sermon-browser'));
        }

        $multi = wp_kses_post($_POST['multi']);
        $single = wp_kses_post($_POST['single']);
        $style = wp_kses_post($_POST['style']);

        if (isset($_POST['resetdefault'])) {
            require(SB_INCLUDES_DIR . '/sb-install.php');
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
        <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/sb-includes/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" /></a>
        <h2><?php _e('Templates', 'sermon-browser'); ?></h2>
        <br/>
        <table border="0" class="widefat">
            <tr>
                <td align="right"><?php _e('Search results page', 'sermon-browser'); ?>: </td>
                <td>
                    <?php sb_build_textarea('multi', sb_get_option('search_template')); ?>
                </td>
            </tr>
            <tr>
                <td align="right"><?php _e('Sermon page', 'sermon-browser'); ?>: </td>
                <td>
                    <?php sb_build_textarea('single', sb_get_option('single_template')); ?>
                </td>
            </tr>
            <tr>
                <td align="right"><?php _e('Style', 'sermon-browser'); ?>: </td>
                <td>
                    <?php sb_build_textarea('style', sb_get_option('css_style')); ?>
                </td>
            </tr>
        </table>
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
