<?php
/**
 * Uninstall Page.
 *
 * Handles the Uninstall admin page for removing SermonBrowser data.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

/**
 * Class UninstallPage
 *
 * Provides uninstall/reset functionality for SermonBrowser.
 */
class UninstallPage
{
    /**
     * Render the uninstall page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!(current_user_can('edit_plugins') || (IS_MU && current_user_can('manage_options')))) {
            wp_die(__("You do not have the correct permissions to Uninstall SermonBrowser", 'sermon-browser'));
        }

        $this->handleUninstall();
        $this->renderForm();
    }

    /**
     * Handle uninstall POST request.
     *
     * @return void
     */
    private function handleUninstall(): void
    {
        if (!isset($_POST['uninstall'])) {
            return;
        }

        if (!isset($_POST['sermon_browser_uninstall_nonce']) ||
            !wp_verify_nonce($_POST['sermon_browser_uninstall_nonce'], 'sermon_browser_uninstall')) {
            wp_die(__("You do not have the correct permissions to Uninstall SermonBrowser", 'sermon-browser'));
        }

        require(SB_INCLUDES_DIR . '/uninstall.php');
    }

    /**
     * Render the uninstall form.
     *
     * @return void
     */
    private function renderForm(): void
    {
        ?>
    <form method="post">
    <div class="wrap">
        <?php if (IS_MU): ?>
            <h2> <?php _e('Reset SermonBrowser', 'sermon-browser'); ?></h2>
            <p><?php
                printf(
                    __('Clicking the %s button below will remove ALL data (sermons, preachers, series, etc.) from SermonBrowser', 'sermon-browser'),
                    __('Delete all', 'sermon-browser')
                );
                echo '. ';
                _e('You will NOT be able to undo this action.', 'sermon-browser');
                ?>
            </p>
        <?php else: ?>
            <h2> <?php _e('Uninstall', 'sermon-browser'); ?></h2>
            <p><?php
                printf(
                    __('Clicking the %s button below will remove ALL data (sermons, preachers, series, etc.) from SermonBrowser', 'sermon-browser'),
                    __('Uninstall', 'sermon-browser')
                );
                echo ', ';
                _e('and will deactivate the SermonBrowser plugin', 'sermon-browser');
                echo '. ';
                _e('You will NOT be able to undo this action.', 'sermon-browser');
                echo ' ';
                _e('If you only want to temporarily disable SermonBrowser, just deactivate it from the plugins page.', 'sermon-browser');
                ?>
            </p>
        <?php endif; ?>
        <table border="0" class="widefat">
            <tr>
                <td><input type="checkbox" name="wipe"
                           value="1"> <?php _e('Also remove all uploaded files', 'sermon-browser'); ?></td>
            </tr>
        </table>
        <p class="submit"><input type="submit" name="uninstall" value="<?php
            if (IS_MU) {
                _e('Delete all', 'sermon-browser');
            } else {
                _e('Uninstall', 'sermon-browser');
            }
            ?>"
                                 onclick="return confirm('<?php _e('Do you REALLY want to delete all data?', 'sermon-browser'); ?>')"/>
        </p>
    </div>
        <?php wp_nonce_field('sermon_browser_uninstall', 'sermon_browser_uninstall_nonce'); ?>
    </form>
    <script>
        jQuery( "form" ).submit( function ()
        {
            var yes = confirm("<?php _e('Are you REALLY REALLY sure you want to remove SermonBrowser?', 'sermon-browser'); ?>");
            if(!yes) return false;
        });
    </script>
        <?php
    }
}
