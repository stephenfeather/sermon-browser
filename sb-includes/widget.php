<?php
/**
 * Widget classes for Sermon Browser
 *
 * Modern WP_Widget implementations for WordPress 4.0+
 * Legacy functions (display_sermons, sb_display_sermons, etc.) are in frontend.php
 *
 * @package sermon-browser
 * @since 0.46.0
 */

/**
 * Modern WP_Widget class for displaying sermons
 *
 * @since 0.46.0
 */
class SB_Sermons_Widget extends WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'sb_sermons',
			__('Sermons', 'sermon-browser'),
			array(
				'classname' => 'sb-sermons-widget',
				'description' => __('Display a list of recent sermons.', 'sermon-browser'),
			)
		);
	}

	/**
	 * Front-end display of widget
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance) {
		$before_widget = isset($args['before_widget']) ? $args['before_widget'] : '';
		$after_widget = isset($args['after_widget']) ? $args['after_widget'] : '';
		$before_title = isset($args['before_title']) ? $args['before_title'] : '';
		$after_title = isset($args['after_title']) ? $args['after_title'] : '';

		$title = !empty($instance['title']) ? $instance['title'] : '';
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		$limit = isset($instance['limit']) ? (int) $instance['limit'] : 5;
		$preacher = isset($instance['preacher']) ? (int) $instance['preacher'] : 0;
		$service = isset($instance['service']) ? (int) $instance['service'] : 0;
		$series = isset($instance['series']) ? (int) $instance['series'] : 0;
		$show_preacher = isset($instance['show_preacher']) ? (bool) $instance['show_preacher'] : false;
		$show_book = isset($instance['show_book']) ? (bool) $instance['show_book'] : false;
		$show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : false;

		echo $before_widget;

		if (!empty($title)) {
			echo $before_title . esc_html($title) . $after_title;
		}

		$sermons = sb_get_sermons(
			array(
				'preacher' => $preacher,
				'service' => $service,
				'series' => $series,
			),
			array(),
			1,
			$limit
		);

		echo '<ul class="sermon-widget">';
		foreach ((array) $sermons as $sermon) {
			echo '<li><span class="sermon-title">';
			echo '<a href="' . esc_url(sb_build_url(array('sermon_id' => $sermon->id), true)) . '">' . esc_html(stripslashes($sermon->title)) . '</a></span>';

			if ($show_book && !empty($sermon->start) && !empty($sermon->end)) {
				$foo = unserialize($sermon->start);
				$bar = unserialize($sermon->end);
				if ($foo && $bar) {
					echo ' <span class="sermon-passage">(' . esc_html(sb_get_books($foo[0], $bar[0])) . ')</span>';
				}
			}

			if ($show_preacher && !empty($sermon->preacher)) {
				echo ' <span class="sermon-preacher">' . esc_html__('by', 'sermon-browser') . ' <a href="';
				sb_print_preacher_link($sermon);
				echo '">' . esc_html(stripslashes($sermon->preacher)) . '</a></span>';
			}

			if ($show_date) {
				echo ' <span class="sermon-date">' . esc_html__(' on ', 'sermon-browser') . esc_html(sb_formatted_date($sermon)) . '</span>';
			}

			echo '.</li>';
		}
		echo '</ul>';

		echo $after_widget;
	}

	/**
	 * Back-end widget form
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form($instance) {
		$title = isset($instance['title']) ? $instance['title'] : '';
		$limit = isset($instance['limit']) ? (int) $instance['limit'] : 5;
		$preacher = isset($instance['preacher']) ? (int) $instance['preacher'] : 0;
		$service = isset($instance['service']) ? (int) $instance['service'] : 0;
		$series = isset($instance['series']) ? (int) $instance['series'] : 0;
		$show_preacher = isset($instance['show_preacher']) ? (bool) $instance['show_preacher'] : false;
		$show_book = isset($instance['show_book']) ? (bool) $instance['show_book'] : false;
		$show_date = isset($instance['show_date']) ? (bool) $instance['show_date'] : false;

		$dpreachers = \SermonBrowser\Facades\Preacher::findAllSorted();
		$dseries = \SermonBrowser\Facades\Series::findAllSorted();
		$dservices = \SermonBrowser\Facades\Service::findAllSorted();
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'sermon-browser'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php esc_html_e('Number of sermons:', 'sermon-browser'); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" min="1" max="20" value="<?php echo esc_attr($limit); ?>" />
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_preacher')); ?>" name="<?php echo esc_attr($this->get_field_name('show_preacher')); ?>" <?php checked($show_preacher); ?> />
			<label for="<?php echo esc_attr($this->get_field_id('show_preacher')); ?>"><?php esc_html_e('Display preacher', 'sermon-browser'); ?></label>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_book')); ?>" name="<?php echo esc_attr($this->get_field_name('show_book')); ?>" <?php checked($show_book); ?> />
			<label for="<?php echo esc_attr($this->get_field_id('show_book')); ?>"><?php esc_html_e('Display bible passage', 'sermon-browser'); ?></label>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('show_date')); ?>" name="<?php echo esc_attr($this->get_field_name('show_date')); ?>" <?php checked($show_date); ?> />
			<label for="<?php echo esc_attr($this->get_field_id('show_date')); ?>"><?php esc_html_e('Display date', 'sermon-browser'); ?></label>
		</p>
		<hr />
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('preacher')); ?>"><?php esc_html_e('Preacher:', 'sermon-browser'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('preacher')); ?>" name="<?php echo esc_attr($this->get_field_name('preacher')); ?>">
				<option value="0" <?php selected($preacher, 0); ?>><?php esc_html_e('[All]', 'sermon-browser'); ?></option>
				<?php foreach ((array) $dpreachers as $p) : ?>
					<option value="<?php echo esc_attr($p->id); ?>" <?php selected($preacher, $p->id); ?>><?php echo esc_html($p->name); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('service')); ?>"><?php esc_html_e('Service:', 'sermon-browser'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('service')); ?>" name="<?php echo esc_attr($this->get_field_name('service')); ?>">
				<option value="0" <?php selected($service, 0); ?>><?php esc_html_e('[All]', 'sermon-browser'); ?></option>
				<?php foreach ((array) $dservices as $s) : ?>
					<option value="<?php echo esc_attr($s->id); ?>" <?php selected($service, $s->id); ?>><?php echo esc_html($s->name); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('series')); ?>"><?php esc_html_e('Series:', 'sermon-browser'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('series')); ?>" name="<?php echo esc_attr($this->get_field_name('series')); ?>">
				<option value="0" <?php selected($series, 0); ?>><?php esc_html_e('[All]', 'sermon-browser'); ?></option>
				<?php foreach ((array) $dseries as $se) : ?>
					<option value="<?php echo esc_attr($se->id); ?>" <?php selected($series, $se->id); ?>><?php echo esc_html($se->name); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
		$instance['limit'] = !empty($new_instance['limit']) ? absint($new_instance['limit']) : 5;
		$instance['preacher'] = !empty($new_instance['preacher']) ? absint($new_instance['preacher']) : 0;
		$instance['service'] = !empty($new_instance['service']) ? absint($new_instance['service']) : 0;
		$instance['series'] = !empty($new_instance['series']) ? absint($new_instance['series']) : 0;
		$instance['show_preacher'] = !empty($new_instance['show_preacher']);
		$instance['show_book'] = !empty($new_instance['show_book']);
		$instance['show_date'] = !empty($new_instance['show_date']);
		return $instance;
	}
}

/**
 * Modern WP_Widget class for displaying sermon tag cloud
 *
 * @since 0.46.0
 */
class SB_Tag_Cloud_Widget extends WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'sb_tag_cloud',
			__('Sermon Browser Tags', 'sermon-browser'),
			array(
				'classname' => 'sb-tag-cloud-widget',
				'description' => __('Display a cloud of sermon tags.', 'sermon-browser'),
			)
		);
	}

	/**
	 * Front-end display of widget
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance) {
		$before_widget = isset($args['before_widget']) ? $args['before_widget'] : '';
		$after_widget = isset($args['after_widget']) ? $args['after_widget'] : '';
		$before_title = isset($args['before_title']) ? $args['before_title'] : '';
		$after_title = isset($args['after_title']) ? $args['after_title'] : '';

		$title = !empty($instance['title']) ? $instance['title'] : __('Sermon Browser Tags', 'sermon-browser');
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		echo $before_widget;

		if (!empty($title)) {
			echo $before_title . esc_html($title) . $after_title;
		}

		sb_print_tag_clouds();

		echo $after_widget;
	}

	/**
	 * Back-end widget form
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form($instance) {
		$title = isset($instance['title']) ? $instance['title'] : __('Sermon Browser Tags', 'sermon-browser');
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'sermon-browser'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
		return $instance;
	}
}

/**
 * Modern WP_Widget class for displaying popular sermons
 *
 * @since 0.46.0
 */
class SB_Popular_Widget extends WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'sb_popular',
			__('Popular Sermons', 'sermon-browser'),
			array(
				'classname' => 'sb-popular-widget',
				'description' => __('Display popular sermons, series, and preachers.', 'sermon-browser'),
			)
		);
	}

	/**
	 * Front-end display of widget
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance) {
		$before_widget = isset($args['before_widget']) ? $args['before_widget'] : '';
		$after_widget = isset($args['after_widget']) ? $args['after_widget'] : '';
		$before_title = isset($args['before_title']) ? $args['before_title'] : '';
		$after_title = isset($args['after_title']) ? $args['after_title'] : '';

		$title = !empty($instance['title']) ? $instance['title'] : '';
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);

		// Build options array for legacy function
		$options = array(
			'title' => $title,
			'limit' => isset($instance['limit']) ? (int) $instance['limit'] : 5,
			'display_sermons' => isset($instance['display_sermons']) ? (bool) $instance['display_sermons'] : true,
			'display_series' => isset($instance['display_series']) ? (bool) $instance['display_series'] : true,
			'display_preachers' => isset($instance['display_preachers']) ? (bool) $instance['display_preachers'] : true,
		);

		// Use the existing sb_widget_popular function with the new args format
		$widget_args = array(
			'before_widget' => $before_widget,
			'after_widget' => $after_widget,
			'before_title' => $before_title,
			'after_title' => $after_title,
			'options' => $options,
			'suffix' => '_w' . $this->number,
		);

		sb_widget_popular($widget_args);
	}

	/**
	 * Back-end widget form
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form($instance) {
		$title = isset($instance['title']) ? $instance['title'] : '';
		$limit = isset($instance['limit']) ? (int) $instance['limit'] : 5;
		$display_sermons = isset($instance['display_sermons']) ? (bool) $instance['display_sermons'] : true;
		$display_series = isset($instance['display_series']) ? (bool) $instance['display_series'] : true;
		$display_preachers = isset($instance['display_preachers']) ? (bool) $instance['display_preachers'] : true;
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'sermon-browser'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php esc_html_e('Number of items:', 'sermon-browser'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>">
				<?php for ($i = 1; $i <= 15; $i++) : ?>
					<option value="<?php echo esc_attr($i); ?>" <?php selected($limit, $i); ?>><?php echo esc_html($i); ?></option>
				<?php endfor; ?>
			</select>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('display_sermons')); ?>" name="<?php echo esc_attr($this->get_field_name('display_sermons')); ?>" <?php checked($display_sermons); ?> />
			<label for="<?php echo esc_attr($this->get_field_id('display_sermons')); ?>"><?php esc_html_e('Display popular sermons', 'sermon-browser'); ?></label>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('display_series')); ?>" name="<?php echo esc_attr($this->get_field_name('display_series')); ?>" <?php checked($display_series); ?> />
			<label for="<?php echo esc_attr($this->get_field_id('display_series')); ?>"><?php esc_html_e('Display popular series', 'sermon-browser'); ?></label>
		</p>
		<p>
			<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('display_preachers')); ?>" name="<?php echo esc_attr($this->get_field_name('display_preachers')); ?>" <?php checked($display_preachers); ?> />
			<label for="<?php echo esc_attr($this->get_field_id('display_preachers')); ?>"><?php esc_html_e('Display popular preachers', 'sermon-browser'); ?></label>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
		$instance['limit'] = !empty($new_instance['limit']) ? absint($new_instance['limit']) : 5;
		$instance['display_sermons'] = !empty($new_instance['display_sermons']);
		$instance['display_series'] = !empty($new_instance['display_series']);
		$instance['display_preachers'] = !empty($new_instance['display_preachers']);
		return $instance;
	}
}
?>