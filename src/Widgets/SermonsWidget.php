<?php

declare(strict_types=1);

namespace SermonBrowser\Widgets;

use SermonBrowser\Constants;
use WP_Widget;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Service;

/**
 * Sermons Widget for displaying recent sermons.
 *
 * Modern WP_Widget class for displaying a list of recent sermons
 * in WordPress sidebars and widget areas.
 *
 * @since 0.46.0
 */
class SermonsWidget extends WP_Widget
{
    /**
     * Constructor.
     *
     * Initializes the widget with ID, name, and options.
     */
    public function __construct()
    {
        parent::__construct(
            'sb_sermons',
            __('Sermons', 'sermon-browser'),
            [
                'classname' => 'sb-sermons-widget',
                'description' => __('Display a list of recent sermons.', 'sermon-browser'),
            ]
        );
    }

    /**
     * Front-end display of widget.
     *
     * Outputs the sermon list HTML on the front-end.
     *
     * @param array<string, string> $args     Widget arguments.
     * @param array<string, mixed>  $instance Saved values from database.
     *
     * @return void
     */
    public function widget($args, $instance): void
    {
        $beforeWidget = $args['before_widget'] ?? '';
        $afterWidget = $args['after_widget'] ?? '';
        $beforeTitle = $args['before_title'] ?? '';
        $afterTitle = $args['after_title'] ?? '';

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        $opts = $this->extractInstanceOptions($instance);

        echo $beforeWidget;

        if (!empty($title)) {
            echo $beforeTitle . esc_html($title) . $afterTitle;
        }

        $sermons = sb_get_sermons(
            [
                'preacher' => $opts['preacher'],
                'service' => $opts['service'],
                'series' => $opts['series'],
            ],
            [],
            1,
            $opts['limit']
        );

        echo '<ul class="sermon-widget">';
        foreach ((array) $sermons as $sermon) {
            $this->renderSermonListItem($sermon, $opts);
        }
        echo '</ul>';

        echo $afterWidget;
    }

    /**
     * Extract instance options with defaults.
     *
     * @param array<string, mixed> $instance Widget instance.
     * @return array<string, mixed> Normalized options.
     */
    private function extractInstanceOptions(array $instance): array
    {
        return [
            'limit' => (int) ($instance['limit'] ?? 5),
            'preacher' => (int) ($instance['preacher'] ?? 0),
            'service' => (int) ($instance['service'] ?? 0),
            'series' => (int) ($instance['series'] ?? 0),
            'show_preacher' => (bool) ($instance['show_preacher'] ?? false),
            'show_book' => (bool) ($instance['show_book'] ?? false),
            'show_date' => (bool) ($instance['show_date'] ?? false),
        ];
    }

    /**
     * Render a single sermon list item.
     *
     * @param object $sermon Sermon object.
     * @param array<string, mixed> $opts Display options.
     * @return void
     */
    private function renderSermonListItem(object $sermon, array $opts): void
    {
        echo '<li><span class="sermon-title">';
        echo '<a href="' . esc_url(sb_build_url(['sermon_id' => $sermon->id], true)) . '">';
        echo esc_html(stripslashes($sermon->title)) . '</a></span>';

        if ($opts['show_book'] && !empty($sermon->start) && !empty($sermon->end)) {
            $this->renderBookPassage($sermon);
        }

        if ($opts['show_preacher'] && !empty($sermon->preacher)) {
            $this->renderPreacherLink($sermon);
        }

        if ($opts['show_date']) {
            echo ' <span class="sermon-date">' . esc_html__(' on ', 'sermon-browser');
            echo esc_html(sb_formatted_date($sermon)) . '</span>';
        }

        echo '.</li>';
    }

    /**
     * Render the book passage span.
     *
     * @param object $sermon Sermon object.
     * @return void
     */
    private function renderBookPassage(object $sermon): void
    {
        $startData = unserialize($sermon->start);
        $endData = unserialize($sermon->end);
        if ($startData && $endData) {
            echo ' <span class="sermon-passage">(';
            echo esc_html(sb_get_books($startData[0], $endData[0])) . ')</span>';
        }
    }

    /**
     * Render the preacher link span.
     *
     * @param object $sermon Sermon object.
     * @return void
     */
    private function renderPreacherLink(object $sermon): void
    {
        echo ' <span class="sermon-preacher">' . esc_html__('by', 'sermon-browser') . ' <a href="';
        sb_print_preacher_link($sermon);
        echo '">' . esc_html(stripslashes($sermon->preacher)) . '</a></span>';
    }

    /**
     * Back-end widget form.
     *
     * Outputs the admin form for configuring the widget.
     *
     * @param array<string, mixed> $instance Previously saved values from database.
     *
     * @return void
     */
    public function form($instance): void
    {
        $title = $instance['title'] ?? '';
        $limit = isset($instance['limit']) ? (int) $instance['limit'] : 5;
        $preacher = isset($instance['preacher']) ? (int) $instance['preacher'] : 0;
        $service = isset($instance['service']) ? (int) $instance['service'] : 0;
        $series = isset($instance['series']) ? (int) $instance['series'] : 0;
        $showPreacher = isset($instance['show_preacher']) ? (bool) $instance['show_preacher'] : false;
        $showBook = isset($instance['show_book']) ? (bool) $instance['show_book'] : false;
        $showDate = isset($instance['show_date']) ? (bool) $instance['show_date'] : false;

        $dpreachers = Preacher::findAllSorted();
        $dseries = Series::findAllSorted();
        $dservices = Service::findAllSorted();
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'sermon-browser'); ?>
            </label>
            <input class="widefat"
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                   type="text"
                   value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">
                <?php esc_html_e('Number of sermons:', 'sermon-browser'); ?>
            </label>
            <input class="tiny-text"
                   id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>"
                   type="number"
                   min="1"
                   max="20"
                   value="<?php echo esc_attr((string) $limit); ?>" />
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_preacher')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_preacher')); ?>"
                   <?php checked($showPreacher); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('show_preacher')); ?>">
                <?php esc_html_e('Display preacher', 'sermon-browser'); ?>
            </label>
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_book')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_book')); ?>"
                   <?php checked($showBook); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('show_book')); ?>">
                <?php esc_html_e('Display bible passage', 'sermon-browser'); ?>
            </label>
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('show_date')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_date')); ?>"
                   <?php checked($showDate); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>">
                <?php esc_html_e('Display date', 'sermon-browser'); ?>
            </label>
        </p>
        <hr />
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('preacher')); ?>">
                <?php esc_html_e('Preacher:', 'sermon-browser'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('preacher')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('preacher')); ?>">
                <option value="0" <?php selected($preacher, 0); ?>>
                    <?php esc_html_e(Constants::ALL_FILTER, 'sermon-browser'); ?>
                </option>
                <?php foreach ((array) $dpreachers as $p) : ?>
                    <option value="<?php echo esc_attr((string) $p->id); ?>" <?php selected($preacher, $p->id); ?>>
                        <?php echo esc_html($p->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('service')); ?>">
                <?php esc_html_e('Service:', 'sermon-browser'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('service')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('service')); ?>">
                <option value="0" <?php selected($service, 0); ?>>
                    <?php esc_html_e(Constants::ALL_FILTER, 'sermon-browser'); ?>
                </option>
                <?php foreach ((array) $dservices as $s) : ?>
                    <option value="<?php echo esc_attr((string) $s->id); ?>" <?php selected($service, $s->id); ?>>
                        <?php echo esc_html($s->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('series')); ?>">
                <?php esc_html_e('Series:', 'sermon-browser'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('series')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('series')); ?>">
                <option value="0" <?php selected($series, 0); ?>>
                    <?php esc_html_e(Constants::ALL_FILTER, 'sermon-browser'); ?>
                </option>
                <?php foreach ((array) $dseries as $se) : ?>
                    <option value="<?php echo esc_attr((string) $se->id); ?>" <?php selected($series, $se->id); ?>>
                        <?php echo esc_html($se->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @param array<string, mixed> $newInstance Values just sent to be saved.
     * @param array<string, mixed> $oldInstance Previously saved values from database.
     *
     * @return array<string, mixed> Updated safe values to be saved.
     */
    public function update($newInstance, $oldInstance): array
    {
        $instance = [];
        $instance['title'] = !empty($newInstance['title']) ? sanitize_text_field($newInstance['title']) : '';
        $instance['limit'] = !empty($newInstance['limit']) ? absint($newInstance['limit']) : 5;
        $instance['preacher'] = !empty($newInstance['preacher']) ? absint($newInstance['preacher']) : 0;
        $instance['service'] = !empty($newInstance['service']) ? absint($newInstance['service']) : 0;
        $instance['series'] = !empty($newInstance['series']) ? absint($newInstance['series']) : 0;
        $instance['show_preacher'] = !empty($newInstance['show_preacher']);
        $instance['show_book'] = !empty($newInstance['show_book']);
        $instance['show_date'] = !empty($newInstance['show_date']);

        return $instance;
    }
}

// Backward compatibility alias
class_alias(SermonsWidget::class, 'SB_Sermons_Widget', false);
