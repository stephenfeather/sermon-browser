<?php

declare(strict_types=1);

namespace SermonBrowser\Widgets;

use WP_Widget;

/**
 * Popular Widget for displaying popular sermons, series, and preachers.
 *
 * Modern WP_Widget class for displaying popular content
 * in WordPress sidebars and widget areas.
 *
 * @extends WP_Widget<array{title?: string, limit?: int, display_sermons?: bool, display_series?: bool, display_preachers?: bool}>
 *
 * @since 0.46.0
 */
class PopularWidget extends WP_Widget
{
    /**
     * Constructor.
     *
     * Initializes the widget with ID, name, and options.
     */
    public function __construct()
    {
        parent::__construct(
            'sb_popular',
            __('Popular Sermons', 'sermon-browser'),
            [
                'classname' => 'sb-popular-widget',
                'description' => __('Display popular sermons, series, and preachers.', 'sermon-browser'),
            ]
        );
    }

    /**
     * Front-end display of widget.
     *
     * Outputs the popular content HTML on the front-end.
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

        // Build options array for legacy function
        $options = [
            'title' => $title,
            'limit' => isset($instance['limit']) ? (int) $instance['limit'] : 5,
            'display_sermons' => isset($instance['display_sermons']) ? (bool) $instance['display_sermons'] : true,
            'display_series' => isset($instance['display_series']) ? (bool) $instance['display_series'] : true,
            'display_preachers' => isset($instance['display_preachers']) ? (bool) $instance['display_preachers'] : true,
        ];

        // Use the existing sb_widget_popular function with the new args format
        $widgetArgs = [
            'before_widget' => $beforeWidget,
            'after_widget' => $afterWidget,
            'before_title' => $beforeTitle,
            'after_title' => $afterTitle,
            'options' => $options,
            'suffix' => '_w' . $this->number,
        ];

        sb_widget_popular($widgetArgs);
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
        $displaySermons = isset($instance['display_sermons']) ? (bool) $instance['display_sermons'] : true;
        $displaySeries = isset($instance['display_series']) ? (bool) $instance['display_series'] : true;
        $displayPreachers = isset($instance['display_preachers']) ? (bool) $instance['display_preachers'] : true;
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
                <?php esc_html_e('Number of items:', 'sermon-browser'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('limit')); ?>">
                <?php for ($i = 1; $i <= 15; $i++) : ?>
                    <option value="<?php echo esc_attr((string) $i); ?>" <?php selected($limit, $i); ?>>
                        <?php echo esc_html((string) $i); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('display_sermons')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('display_sermons')); ?>"
                   <?php checked($displaySermons); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('display_sermons')); ?>">
                <?php esc_html_e('Display popular sermons', 'sermon-browser'); ?>
            </label>
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('display_series')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('display_series')); ?>"
                   <?php checked($displaySeries); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('display_series')); ?>">
                <?php esc_html_e('Display popular series', 'sermon-browser'); ?>
            </label>
        </p>
        <p>
            <input type="checkbox"
                   id="<?php echo esc_attr($this->get_field_id('display_preachers')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('display_preachers')); ?>"
                   <?php checked($displayPreachers); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('display_preachers')); ?>">
                <?php esc_html_e('Display popular preachers', 'sermon-browser'); ?>
            </label>
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
        $instance['display_sermons'] = !empty($newInstance['display_sermons']);
        $instance['display_series'] = !empty($newInstance['display_series']);
        $instance['display_preachers'] = !empty($newInstance['display_preachers']);

        return $instance;
    }
}

// Backward compatibility alias
class_alias(PopularWidget::class, 'SB_Popular_Widget', false);
