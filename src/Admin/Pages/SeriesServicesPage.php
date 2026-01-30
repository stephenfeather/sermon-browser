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

/**
 * Class SeriesServicesPage
 *
 * Manages series and services CRUD operations via AJAX.
 */
class SeriesServicesPage
{
    /**
     * WordPress database instance.
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

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

        // Load data.
        $series = $this->loadSeries();
        $services = $this->loadServices();

        $toManage = [
            'Series' => ['data' => $series],
            'Services' => ['data' => $services],
        ];

        sb_do_alerts();

        // Render JavaScript.
        $this->renderJavaScript();

        // Render anchor.
        echo '<a name="top"></a>';

        // Render each section.
        foreach ($toManage as $type => $data) {
            $this->renderSection($type, $data['data']);
        }
    }

    /**
     * Load series data with sermon counts.
     *
     * @return array
     */
    private function loadSeries(): array
    {
        return $this->wpdb->get_results(
            "SELECT {$this->wpdb->prefix}sb_series.*,
                    COUNT({$this->wpdb->prefix}sb_sermons.id) AS sermon_count
             FROM {$this->wpdb->prefix}sb_series
             LEFT JOIN {$this->wpdb->prefix}sb_sermons
                ON series_id = {$this->wpdb->prefix}sb_series.id
             GROUP BY {$this->wpdb->prefix}sb_series.id
             ORDER BY name ASC"
        );
    }

    /**
     * Load services data with sermon counts.
     *
     * @return array
     */
    private function loadServices(): array
    {
        return $this->wpdb->get_results(
            "SELECT {$this->wpdb->prefix}sb_services.*,
                    COUNT({$this->wpdb->prefix}sb_sermons.id) AS sermon_count
             FROM {$this->wpdb->prefix}sb_services
             LEFT JOIN {$this->wpdb->prefix}sb_sermons
                ON service_id = {$this->wpdb->prefix}sb_services.id
             GROUP BY {$this->wpdb->prefix}sb_services.id
             ORDER BY name ASC"
        );
    }

    /**
     * Render the JavaScript for AJAX operations.
     *
     * @return void
     */
    private function renderJavaScript(): void
    {
        $ajaxUrl = admin_url('admin.php?page=sermon-browser/sermon.php');
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
                    s = prompt("<?php echo esc_js(__("New service's name @ default time?", 'sermon-browser')); ?>", "<?php echo esc_js(__("Service's name @ 18:00", 'sermon-browser')); ?>");
                    if (s == null) { break; }
                }
                if (s != null) {
                    jQuery.post('<?php echo esc_url($ajaxUrl); ?>', {sname: s, sermon: 1}, function(r) {
                        if (r) {
                            sz = s.match(/(.*?)@(.*)/)[1];
                            t = s.match(/(.*?)@(.*)/)[2];
                            jQuery('#Services-list').append('\
                                <tr style="display:none" class="Services" id="rowServices' + r + '">\
                                    <th style="text-align:center" scope="row">' + r + '</th>\
                                    <td id="Services' + r + '">' + sz + '</td>\
                                    <td style="text-align:center">' + t + '</td>\
                                    <td style="text-align:center">\
                                        <a id="linkServices' + r + '" href="javascript:renameServices(' + r + ', \'' + sz + '\')">Edit</a> | <a onclick="return confirm(\'Are you sure?\');" href="javascript:deleteServices(' + r + ')">Delete</a>\
                                    </td>\
                                </tr>\
                            ');
                            jQuery('#rowServices' + r).fadeIn(function() {
                                updateClass('Services');
                            });
                        };
                    });
                }
            }

            function createNewSeries(s) {
                var ss = prompt("<?php echo esc_js(__("New series' name?", 'sermon-browser')); ?>", "<?php echo esc_js(__("Series' name", 'sermon-browser')); ?>");
                if (ss != null) {
                    jQuery.post('<?php echo esc_url($ajaxUrl); ?>', {ssname: ss, sermon: 1}, function(r) {
                        if (r) {
                            jQuery('#Series-list').append('\
                                <tr style="display:none" class="Series" id="rowSeries' + r + '">\
                                    <th style="text-align:center" scope="row">' + r + '</th>\
                                    <td id="Series' + r + '">' + ss + '</td>\
                                    <td style="text-align:center">\
                                        <a id="linkSeries' + r + '" href="javascript:renameSeries(' + r + ', \'' + ss + '\')">Rename</a> | <a onclick="return confirm(\'Are you sure?\');" href="javascript:deleteSeries(' + r + ')">Delete</a>\
                                    </td>\
                                </tr>\
                            ');
                            jQuery('#rowSeries' + r).fadeIn(function() {
                                updateClass('Series');
                            });
                        };
                    });
                }
            }

            function deleteSeries(id) {
                jQuery.post('<?php echo esc_url($ajaxUrl); ?>', {ssname: 'dummy', ssid: id, del: 1, sermon: 1}, function(r) {
                    if (r) {
                        jQuery('#rowSeries' + id).fadeOut(function() {
                            updateClass('Series');
                        });
                    };
                });
            }

            function deleteServices(id) {
                jQuery.post('<?php echo esc_url($ajaxUrl); ?>', {sname: 'dummy', sid: id, del: 1, sermon: 1}, function(r) {
                    if (r) {
                        jQuery('#rowServices' + id).fadeOut(function() {
                            updateClass('Services');
                        });
                    };
                });
            }

            function renameSeries(id, old) {
                var ss = prompt("<?php echo esc_js(__("New series' name?", 'sermon-browser')); ?>", old);
                if (ss != null) {
                    jQuery.post('<?php echo esc_url($ajaxUrl); ?>', {ssid: id, ssname: ss, sermon: 1}, function(r) {
                        if (r) {
                            jQuery('#Series' + id).text(ss);
                            jQuery('#linkSeries' + id).attr('href', 'javascript:renameSeries(' + id + ', "' + ss + '")');
                        };
                    });
                }
            }

            function renameServices(id, old) {
                var s = 'lol';
                while ((s.indexOf('@') == -1) || (s.match(/(.*?)@(.*)/)[2].match(/[0-9]{1,2}:[0-9]{1,2}/) == null)) {
                    s = prompt("<?php echo esc_js(__("New service's name @ default time?", 'sermon-browser')); ?>", old);
                    if (s == null) { break; }
                }
                if (s != null) {
                    jQuery.post('<?php echo esc_url($ajaxUrl); ?>', {sid: id, sname: s, sermon: 1}, function(r) {
                        if (r) {
                            sz = s.match(/(.*?)@(.*)/)[1];
                            t = s.match(/(.*?)@(.*)/)[2];
                            jQuery('#Services' + id).text(sz);
                            jQuery('#time' + id).text(t);
                            jQuery('#linkServices' + id).attr('href', 'javascript:renameServices(' + id + ', "' + s + '")');
                        };
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
        <a name="manage-<?php echo esc_attr($type); ?>"></a>
        <div class="wrap">
            <?php if ($type === 'Series'): ?>
                <a href="http://www.sermonbrowser.com/">
                    <img src="<?php echo SB_PLUGIN_URL; ?>/sb-includes/logo-small.png"
                         width="191" height="35" style="margin: 1em 2em; float: right;"/>
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
                    <?php if ($type === 'Services'): ?>
                        <th scope="col">
                            <div style="text-align:center"><?php _e('Default time', 'sermon-browser'); ?></div>
                        </th>
                    <?php endif; ?>
                    <th scope="col" style="text-align:center"><?php _e('Sermons', 'sermon-browser'); ?></th>
                    <th scope="col" style="text-align:center"><?php _e('Actions', 'sermon-browser'); ?></th>
                </tr>
                </thead>
                <tbody id="<?php echo esc_attr($type); ?>-list">
                <?php if (is_array($data)): ?>
                    <?php foreach ($data as $item): ?>
                        <?php $rowClass = (++$i % 2 === 0) ? 'alternate' : ''; ?>
                        <tr class="<?php echo esc_attr($type . ' ' . $rowClass); ?>"
                            id="row<?php echo esc_attr($type . $item->id); ?>">
                            <th style="text-align:center" scope="row"><?php echo (int) $item->id; ?></th>
                            <td id="<?php echo esc_attr($type . $item->id); ?>">
                                <?php echo esc_html(stripslashes($item->name)); ?>
                            </td>
                            <?php if ($type === 'Services'): ?>
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
