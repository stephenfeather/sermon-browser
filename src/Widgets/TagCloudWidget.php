<?php

declare(strict_types=1);

namespace SermonBrowser\Widgets;

use WP_Widget;

/**
 * Tag Cloud Widget for displaying sermon tags.
 *
 * Modern WP_Widget class for displaying a cloud of sermon tags
 * in WordPress sidebars and widget areas.
 *
 * @since 0.46.0
 */
class TagCloudWidget extends WP_Widget
{
    /**
     * Constructor.
     *
     * Initializes the widget with ID, name, and options.
     */
    public function __construct()
    {
        parent::__construct(
            'sb_tag_cloud',
            __('Sermon Browser Tags', 'sermon-browser'),
            [
                'classname' => 'sb-tag-cloud-widget',
                'description' => __('Display a cloud of sermon tags.', 'sermon-browser'),
            ]
        );
    }

    /**
     * Front-end display of widget.
     *
     * Outputs the tag cloud HTML on the front-end.
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

        $title = !empty($instance['title']) ? $instance['title'] : __('Sermon Browser Tags', 'sermon-browser');
        $title = apply_filters('widget_title', $title, $instance, $this->id_base);

        echo $beforeWidget;

        if (!empty($title)) {
            echo $beforeTitle . esc_html($title) . $afterTitle;
        }

        sb_print_tag_clouds();

        echo $afterWidget;
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
        $title = $instance['title'] ?? __('Sermon Browser Tags', 'sermon-browser');
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

        return $instance;
    }
}

// Backward compatibility alias
class_alias(TagCloudWidget::class, 'SB_Tag_Cloud_Widget', false);
