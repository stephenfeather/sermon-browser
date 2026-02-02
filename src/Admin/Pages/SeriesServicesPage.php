<?php

/**
 * Series & Services Page.
 *
 * Handles the Series and Services admin page for managing both entities.
 *
 * @package SermonBrowser\Admin\Pages
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin\Pages;

use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;

/**
 * Class SeriesServicesPage
 *
 * Manages series and services CRUD operations via AJAX.
 */
class SeriesServicesPage
{
    /**
     * Render the series/services page.
     *
     * @return void
     */
    public function render(): void
    {
        // Security check.
        if (!current_user_can('manage_categories')) {
            wp_die(__("You do not have the correct permissions to manage the series and services database", 'sermon-browser'));
        }

        // Load data using Facades.
        $series = Series::findAllWithSermonCount();
        $services = Service::findAllWithSermonCount();

        $toManage = [
            'Series' => ['data' => $series],
            'Services' => ['data' => $services],
        ];

        sb_do_alerts();

        // Render JavaScript using SBAdmin module.
        $this->renderJavaScript();

        // Render anchor.
        echo '<a name="top"></a>';

        // Render each section.
        foreach ($toManage as $type => $data) {
            $this->renderSection($type, $data['data']);
        }
    }

    /**
     * Render the JavaScript for AJAX operations using SBAdmin module.
     *
     * @return void
     */
    private function renderJavaScript(): void
    {
        ?>
        <script type="text/javascript">
            //<![CDATA[
            function updateClass(type) {
                jQuery('.' + type + ':visible').each(function(i) {
                    jQuery(this).removeClass('alternate');
                    if (++i % 2 == 0) {
                        jQuery(this).addClass('alternate');
                    }
                });
            }

            function createNewServices(s) {
                var s = 'lol';
                while ((s.indexOf('@') == -1) || (s.match(/(.*?)@(.*)/)[2].match(/[0-9]{1,2}:[0-9]{1,2}/) == null)) {
                    s = prompt("<?php _e("New service's name @ default time?", 'sermon-browser'); ?>", "<?php _e("Service's name @ 18:00", 'sermon-browser'); ?>");
                    if (s == null) { break; }
                }
                if (s != null) {
                    SBAdmin.service.create(s).done(function(response) {
                        SBAdmin.handleResponse(response, function(data) {
                            var sz = s.match(/(.*?)@(.*)/)[1];
                            var t = s.match(/(.*?)@(.*)/)[2];
                            jQuery('#Services-list').append('\
                                <tr style="display:none" class="Services" id="rowServices' + data.id + '">\
                                    <th style="text-align:center" scope="row">' + data.id + '</th>\
                                    <td id="Services' + data.id + '">' + sz + '</td>\
                                    <td style="text-align:center">' + t + '</td>\
                                    <td style="text-align:center">\
                                        <a id="linkServices' + data.id + '" href="javascript:renameServices(' + data.id + ', \'' + sz + '\')">Edit</a> | <a onclick="return confirm(\'Are you sure?\');" href="javascript:deleteServices(' + data.id + ')">Delete</a>\
                                    </td>\
                                </tr>\
                            ');
                            jQuery('#rowServices' + data.id).fadeIn(function() {
                                updateClass('Services');
                            });
                        });
                    });
                }
            }

            function createNewSeries(s) {
                var ss = prompt("<?php _e("New series' name?", 'sermon-browser'); ?>", "<?php _e("Series' name", 'sermon-browser'); ?>");
                if (ss != null) {
                    SBAdmin.series.create(ss).done(function(response) {
                        SBAdmin.handleResponse(response, function(data) {
                            jQuery('#Series-list').append('\
                                <tr style="display:none" class="Series" id="rowSeries' + data.id + '">\
                                    <th style="text-align:center" scope="row">' + data.id + '</th>\
                                    <td id="Series' + data.id + '">' + data.name + '</td>\
                                    <td style="text-align:center">\
                                        <a id="linkSeries' + data.id + '" href="javascript:renameSeries(' + data.id + ', \'' + data.name + '\')">Rename</a> | <a onclick="return confirm(\'Are you sure?\');" href="javascript:deleteSeries(' + data.id + ')">Delete</a>\
                                    </td>\
                                </tr>\
                            ');
                            jQuery('#rowSeries' + data.id).fadeIn(function() {
                                updateClass('Series');
                            });
                        });
                    });
                }
            }

            function deleteSeries(id) {
                SBAdmin.series.delete(id).done(function(response) {
                    SBAdmin.handleResponse(response, function() {
                        jQuery('#rowSeries' + id).fadeOut(function() {
                            updateClass('Series');
                        });
                    });
                });
            }

            function deleteServices(id) {
                SBAdmin.service.delete(id).done(function(response) {
                    SBAdmin.handleResponse(response, function() {
                        jQuery('#rowServices' + id).fadeOut(function() {
                            updateClass('Services');
                        });
                    });
                });
            }

            function renameSeries(id, old) {
                var ss = prompt("<?php _e("New series' name?", 'sermon-browser'); ?>", old);
                if (ss != null) {
                    SBAdmin.series.update(id, ss).done(function(response) {
                        SBAdmin.handleResponse(response, function(data) {
                            jQuery('#Series' + id).text(data.name);
                            jQuery('#linkSeries' + id).attr('href', 'javascript:renameSeries(' + id + ', "' + data.name + '")');
                        });
                    });
                }
            }

            function renameServices(id, old) {
                var s = 'lol';
                while ((s.indexOf('@') == -1) || (s.match(/(.*?)@(.*)/)[2].match(/[0-9]{1,2}:[0-9]{1,2}/) == null)) {
                    s = prompt("<?php _e("New service's name @ default time?", 'sermon-browser'); ?>", old);
                    if (s == null) { break; }
                }
                if (s != null) {
                    SBAdmin.service.update(id, s).done(function(response) {
                        SBAdmin.handleResponse(response, function(data) {
                            jQuery('#Services' + id).text(data.name);
                            jQuery('#time' + id).text(data.time);
                            jQuery('#linkServices' + id).attr('href', 'javascript:renameServices(' + id + ', "' + data.name + ' @ ' + data.time + '")');
                        });
                    });
                }
            }
            //]]>
        </script>
        <?php
    }

    /**
     * Render a section (Series or Services).
     *
     * @param string $type Type name (Series or Services).
     * @param array  $data Data array.
     * @return void
     */
    private function renderSection(string $type, array $data): void
    {
        $i = 0;
        ?>
        <div class="wrap" id="manage-<?php echo esc_attr($type); ?>">
            <?php if ($type === 'Series') : ?>
                <a href="http://www.sermonbrowser.com/">
                    <img src="<?php echo SB_PLUGIN_URL; ?>/assets/images/logo-small.png"
                         width="191" height="35" style="margin: 1em 2em; float: right;"
                         alt="<?php esc_attr_e('Sermon Browser logo', 'sermon-browser'); ?>"/>
                </a>
            <?php endif; ?>

            <h2>
                <?php echo esc_html($type); ?>
                (<a href="javascript:createNew<?php echo esc_attr($type); ?>()"><?php _e('add new', 'sermon-browser'); ?></a>)
            </h2>
            <br style="clear:both">

            <table class="widefat" style="width:auto">
                <thead>
                <tr>
                    <th scope="col" style="text-align:center"><?php _e('ID', 'sermon-browser'); ?></th>
                    <th scope="col"><?php _e('Name', 'sermon-browser'); ?></th>
                    <?php if ($type === 'Services') : ?>
                        <th scope="col">
                            <div style="text-align:center"><?php _e('Default time', 'sermon-browser'); ?></div>
                        </th>
                    <?php endif; ?>
                    <th scope="col" style="text-align:center"><?php _e('Sermons', 'sermon-browser'); ?></th>
                    <th scope="col" style="text-align:center"><?php _e('Actions', 'sermon-browser'); ?></th>
                </tr>
                </thead>
                <tbody id="<?php echo esc_attr($type); ?>-list">
                <?php if (is_array($data)) : ?>
                    <?php foreach ($data as $item) : ?>
                        <?php $rowClass = (++$i % 2 === 0) ? 'alternate' : ''; ?>
                        <tr class="<?php echo esc_attr($type . ' ' . $rowClass); ?>"
                            id="row<?php echo esc_attr($type . $item->id); ?>">
                            <th style="text-align:center" scope="row"><?php echo (int) $item->id; ?></th>
                            <td id="<?php echo esc_attr($type . $item->id); ?>">
                                <?php echo esc_html(stripslashes($item->name)); ?>
                            </td>
                            <?php if ($type === 'Services') : ?>
                                <td style="text-align:center" id="time<?php echo (int) $item->id; ?>">
                                    <?php echo esc_html($item->time); ?>
                                </td>
                            <?php endif; ?>
                            <td style="text-align:center"><?php echo (int) $item->sermon_count; ?></td>
                            <td style="text-align:center">
                                <?php $this->renderActions($type, $item, count($data)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <br style="clear:both">
            <div style="text-align:right"><a href="#top">Top &dagger;</a></div>
        </div>
        <?php
    }

    /**
     * Render action links for a row.
     *
     * @param string $type  Type name (Series or Services).
     * @param object $item  Item object.
     * @param int    $total Total count of items.
     * @return void
     */
    private function renderActions(string $type, object $item, int $total): void
    {
        $editLabel = ($type === 'Services') ? __('Edit', 'sermon-browser') : __('Rename', 'sermon-browser');
        $editValue = $item->name . (($type === 'Services') ? ' @ ' . $item->time : '');

        echo '<a id="link' . esc_attr($type . $item->id) . '" href="javascript:rename' .
            esc_attr($type) . '(' . (int) $item->id . ', \'' . esc_js($editValue) . '\')">' .
            esc_html($editLabel) . '</a>';

        if ($total < 2) {
            // Can't delete the only item.
            $msg = sprintf(
                __('You cannot delete this %1$s as you must have at least one %1$s in the database', 'sermon-browser'),
                $type
            );
            echo ' | <a href="javascript:alert(\'' . esc_js($msg) . '\')">' .
                __('Delete', 'sermon-browser') . '</a>';
        } elseif ($item->sermon_count == 0) {
            // Can delete.
            $confirmMsg = sprintf(__('Are you sure you want to delete %s?', 'sermon-browser'), $item->name);
            echo ' | <a href="javascript:if(confirm(\'' . esc_js($confirmMsg) . '\')){delete' .
                esc_attr($type) . '(' . (int) $item->id . ')}">' . __('Delete', 'sermon-browser') . '</a>';
        } else {
            // Has sermons - can't delete.
            $msg = $this->getCannotDeleteMessage($type);
            echo ' | <a href="javascript:alert(\'' . esc_js($msg) . '\')">' .
                __('Delete', 'sermon-browser') . '</a>';
        }
    }

    /**
     * Get the appropriate "cannot delete" message for a type.
     *
     * @param string $type Type name.
     * @return string
     */
    private function getCannotDeleteMessage(string $type): string
    {
        switch ($type) {
            case 'Services':
                return __('Some sermons are currently assigned to that service. You can only delete services that are not used in the database.', 'sermon-browser');
            case 'Series':
                return __('Some sermons are currently in that series. You can only delete series that are empty.', 'sermon-browser');
            case 'Preachers':
                return __('That preacher has sermons in the database. You can only delete preachers who have no sermons in the database.', 'sermon-browser');
            default:
                return __('Cannot delete this item.', 'sermon-browser');
        }
    }
}
