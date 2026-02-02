<?php

/**
 * Sermons Page.
 *
 * Handles the Sermons admin page for managing sermon records.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

use SermonBrowser\Facades\Sermon;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Tag;
use SermonBrowser\Facades\Book;
use SermonBrowser\Facades\File;

/**
 * Class SermonsPage
 *
 * Displays sermon list with filtering and deletion capabilities.
 */
class SermonsPage
{
    /**
     * Render the sermons page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!(current_user_can('publish_posts') || current_user_can('publish_pages'))) {
            wp_die(__("You do not have the correct permissions to edit sermons", 'sermon-browser'));
        }

        sb_do_alerts();

        $this->handleSavedMessage();
        $this->handleDeletion();

        // Build filter array from GET params
        $filters = $this->extractFilters();

        $cnt = Sermon::count();
        $sermons = Sermon::findForAdminListFiltered($filters, (int) sb_get_option('sermons_per_page'));
        $preachers = Preacher::findAllSorted();
        $series = Series::findAllSorted();

        $this->renderPage($cnt, $sermons, $preachers, $series, $filters);
    }

    /**
     * Display saved message if applicable.
     *
     * @return void
     */
    private function handleSavedMessage(): void
    {
        if (!isset($_GET['saved'])) {
            return;
        }

        echo '<div id="message" class="updated fade"><p><b>' .
            __('Sermon saved to database.', 'sermon-browser') . '</b></div>';

        $show_msg = rand(1, 5);
        if ($show_msg == 1 && sb_get_option('show_donate_reminder') != 'off') {
            echo '<div id="message" class="updated"><p><b>' .
                sprintf(
                    __('If you find SermonBrowser useful, please consider %1$ssupporting%2$s the ministry of Nathanael and Anna Ayling in Japan.', 'sermon-browser'),
                    '<a href="' . admin_url('admin.php?page=sermon-browser/japan.php') . '">',
                    '</a>'
                ) . '</b></div>';
        } elseif ($show_msg == 2) {
            echo '<div id="message" class="updated"><p><b>' .
                __('Sermon Browser 2.0 is under development. If you\'re a coder, and would like to help, please check the <a href="https://www.assembla.com/spaces/sermon-browser-2/documents">SB2 development website</a>.', 'sermon-browser') .
                '</b></div>';
        }
    }

    /**
     * Handle sermon deletion.
     *
     * @return void
     */
    private function handleDeletion(): void
    {
        if (!isset($_GET['mid'])) {
            return;
        }

        if (!wp_verify_nonce($_GET['sermon_manage_sermons_nonce'], 'sermon_manage_sermons')) {
            wp_die(__("You do not have the correct permissions to edit sermons", 'sermon-browser'));
        }

        // Security check.
        if (!current_user_can('publish_posts')) {
            wp_die(__("You do not have the correct permissions to delete sermons", 'sermon-browser'));
        }

        $mid = (int) $_GET['mid'];

        // Use Facades for deletion operations.
        Sermon::delete($mid);
        Tag::detachAllFromSermon($mid);
        Book::deleteBySermonId($mid);
        File::unlinkFromSermon($mid);
        File::deleteNonFilesBySermon($mid);
        sb_delete_unused_tags();

        echo '<div id="message" class="updated fade"><p><b>' .
            __('Sermon removed from database.', 'sermon-browser') . '</b></div>';
    }

    /**
     * Extract filter values from GET parameters.
     *
     * @return array<string, mixed> Filter criteria for repository query.
     */
    private function extractFilters(): array
    {
        $filters = [];

        // Title search (sanitize as text field)
        if (!empty($_GET['title'])) {
            $filters['title'] = sanitize_text_field(wp_unslash($_GET['title']));
        }

        // Preacher filter (integer ID)
        if (!empty($_GET['preacher_id']) && (int) $_GET['preacher_id'] > 0) {
            $filters['preacher_id'] = (int) $_GET['preacher_id'];
        }

        // Series filter (integer ID)
        if (!empty($_GET['series_id']) && (int) $_GET['series_id'] > 0) {
            $filters['series_id'] = (int) $_GET['series_id'];
        }

        return $filters;
    }

    /**
     * Render the page content.
     *
     * @param int   $cnt       Total sermon count.
     * @param array $sermons   Sermon list.
     * @param array $preachers Preacher list.
     * @param array $series    Series list.
     * @param array $filters   Current filter values.
     * @return void
     */
    private function renderPage(int $cnt, array $sermons, array $preachers, array $series, array $filters): void
    {
        $this->renderScript();
        $this->renderFilterForm($preachers, $series, $filters);
        $this->renderSermonsTable($sermons);
        $this->renderNavigation($cnt);
    }

    /**
     * Render JavaScript for AJAX pagination using SBAdmin module.
     *
     * @return void
     */
    private function renderScript(): void
    {
        ?>
    <script>
        var currentSermonPage = 1;
        function fetch(page) {
            if (typeof page === 'undefined') page = 1;
            SBAdmin.sermon.list(page, {
                title: jQuery('#search').val(),
                preacher: jQuery('#preacher').val(),
                series: jQuery('#series').val()
            }).done(function(response) {
                SBAdmin.handleResponse(response, function(data) {
                    currentSermonPage = data.page;
                    if (data.items.length > 0) {
                        jQuery('#the-list').html(SBAdmin.sermon.renderRows(data.items));
                    } else {
                        jQuery('#the-list').html('<tr><td colspan="8">' + SBAdmin.i18n.noResults + '</td></tr>');
                    }
                    jQuery('#left').html(data.has_prev ? '<a href="javascript:fetch(' + (data.page - 1) + ')">' + SBAdmin.i18n.previous + '</a>' : '');
                    jQuery('#right').html(data.has_next ? '<a href="javascript:fetch(' + (data.page + 1) + ')">' + SBAdmin.i18n.next + '</a>' : '');
                });
            });
        }
    </script>
        <?php
    }

    /**
     * Render the filter form.
     *
     * @param array $preachers Preacher list.
     * @param array $series    Series list.
     * @param array $filters   Current filter values.
     * @return void
     */
    private function renderFilterForm(array $preachers, array $series, array $filters): void
    {
        $currentTitle = esc_attr($filters['title'] ?? '');
        $currentPreacherId = (int) ($filters['preacher_id'] ?? 0);
        $currentSeriesId = (int) ($filters['series_id'] ?? 0);
        ?>
    <div class="wrap">
            <a href="http://www.sermonbrowser.com/"><img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png" width="191" height ="35" style="margin: 1em 2em; float: right;" alt="<?php esc_attr_e('Sermon Browser logo', 'sermon-browser'); ?>" /></a>
            <h2>Filter</h2>
            <form id="searchform" name="searchform">
            <fieldset style="float:left; margin-right: 1em">
                <legend><?php _e('Title', 'sermon-browser'); ?></legend>
                <input type="text" size="17" value="<?php echo $currentTitle; ?>" id="search" />
            </fieldset>
            <fieldset style="float:left; margin-right: 1em">
                <legend><?php _e('Preacher', 'sermon-browser'); ?></legend>
                <select id="preacher">
                    <option value="0"></option>
                    <?php foreach ($preachers as $preacher) : ?>
                        <option value="<?php echo $preacher->id; ?>"<?php selected($preacher->id, $currentPreacherId); ?>><?php echo htmlspecialchars(stripslashes($preacher->name), ENT_QUOTES); ?></option>
                    <?php endforeach; ?>
                </select>
            </fieldset>
            <fieldset style="float:left; margin-right: 1em">
                <legend><?php _e('Series', 'sermon-browser'); ?></legend>
                <select id="series">
                    <option value="0"></option>
                    <?php foreach ($series as $item) : ?>
                        <option value="<?php echo $item->id; ?>"<?php selected($item->id, $currentSeriesId); ?>><?php echo htmlspecialchars(stripslashes($item->name), ENT_QUOTES); ?></option>
                    <?php endforeach; ?>
                </select>
            </fieldset style="float:left; margin-right: 1em">
            <input type="submit" class="button" value="<?php _e('Filter', 'sermon-browser'); ?> &raquo;" style="float:left;margin:14px 0pt 1em; position:relative;top:0.35em;" onclick="javascript:fetch(0);return false;" />
            </form>
        <br style="clear:both">
        <?php
    }

    /**
     * Render the sermons table.
     *
     * @param array $sermons Sermon list.
     * @return void
     */
    private function renderSermonsTable(array $sermons): void
    {
        ?>
        <h2><?php _e('Sermons', 'sermon-browser'); ?></h2>
        <br style="clear:both">
        <table class="widefat">
            <thead>
            <tr>
                <th scope="col" style="text-align:center"><?php _e('ID', 'sermon-browser'); ?></th>
                <th scope="col"><?php _e('Title', 'sermon-browser'); ?></th>
                <th scope="col"><?php _e('Preacher', 'sermon-browser'); ?></th>
                <th scope="col"><?php _e('Date', 'sermon-browser'); ?></th>
                <th scope="col"><?php _e('Service', 'sermon-browser'); ?></th>
                <th scope="col"><?php _e('Series', 'sermon-browser'); ?></th>
                <th scope="col" style="text-align:center"><?php _e('Stats', 'sermon-browser'); ?></th>
                <th scope="col" style="text-align:center"><?php _e('Actions', 'sermon-browser'); ?></th>
            </tr>
            </thead>
            <tbody id="the-list">
                <?php $this->renderSermonRows($sermons); ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render individual sermon rows.
     *
     * @param array $sermons Sermon list.
     * @return void
     */
    private function renderSermonRows(array $sermons): void
    {
        if (empty($sermons)) {
            return;
        }

        $i = 0;
        foreach ($sermons as $sermon) {
            $rowClass = ++$i % 2 == 0 ? 'alternate' : '';
            $dateDisplay = ($sermon->datetime == '1970-01-01 00:00:00')
                ? __('Unknown', 'sermon-browser')
                : wp_date('d M y', strtotime($sermon->datetime));
            ?>
            <tr class="<?php echo $rowClass; ?>">
                <th style="text-align:center" scope="row"><?php echo $sermon->id; ?></th>
                <td><?php echo stripslashes($sermon->title); ?></td>
                <td><?php echo stripslashes($sermon->pname); ?></td>
                <td><?php echo $dateDisplay; ?></td>
                <td><?php echo stripslashes($sermon->sname); ?></td>
                <td><?php echo stripslashes($sermon->ssname); ?></td>
                <td><?php echo sb_sermon_stats($sermon->id); ?></td>
                <td style="text-align:center">
                    <?php $this->renderSermonActions($sermon); ?>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Render actions for a sermon row.
     *
     * @param object $sermon Sermon object.
     * @return void
     */
    private function renderSermonActions(object $sermon): void
    {
        // Security check.
        if (current_user_can('publish_posts')) {
            $editUrl = wp_nonce_url(
                admin_url("admin.php?page=sermon-browser/new_sermon.php&mid={$sermon->id}"),
                'sermon_new_sermons',
                'sermon_new_sermons_nonce'
            );
            $deleteUrl = wp_nonce_url(
                admin_url("admin.php?page=sermon-browser/sermon.php&mid={$sermon->id}"),
                'sermon_manage_sermons',
                'sermon_manage_sermons_nonce'
            );
            ?>
            <a href="<?php echo $editUrl; ?>"><?php _e('Edit', 'sermon-browser'); ?></a> | <a onclick="return confirm('Are you sure?')" href="<?php echo $deleteUrl; ?>"><?php _e('Delete', 'sermon-browser'); ?></a> |
            <?php
        }
        ?>
        <a href="<?php echo sb_display_url() . sb_query_char(true) . 'sermon_id=' . $sermon->id; ?>">View</a>
        <?php
    }

    /**
     * Render navigation controls.
     *
     * @param int $cnt Total sermon count.
     * @return void
     */
    private function renderNavigation(int $cnt): void
    {
        $sermonsPerPage = sb_get_option('sermons_per_page');
        ?>
        <div class="navigation">
            <div class="alignleft" id="left"></div>
            <div class="alignright" id="right"></div>
        </div>
    </div>
    <script>
        <?php if ($cnt > $sermonsPerPage) : ?>
            jQuery('#right').html('<a href="javascript:fetch(<?php echo $sermonsPerPage; ?>)">Next &raquo;</a>');
        <?php endif; ?>
    </script>
        <?php
    }
}
