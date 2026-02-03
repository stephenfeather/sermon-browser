<?php

/**
 * Server-side rendering of the sermon-browser/profile-block block.
 *
 * @package sermon-browser
 * @since 0.8.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 */

declare(strict_types=1);

// Ensure we're in WordPress context.
if (!defined('ABSPATH')) {
    exit;
}

$profile_type = isset($attributes['profileType']) ? sanitize_text_field($attributes['profileType']) : 'preacher';
$profile_id = isset($attributes['profileId']) ? (int) $attributes['profileId'] : 0;
$show_image = isset($attributes['showImage']) ? (bool) $attributes['showImage'] : true;
$show_bio = isset($attributes['showBio']) ? (bool) $attributes['showBio'] : true;
$show_sermons = isset($attributes['showSermons']) ? (bool) $attributes['showSermons'] : true;
$sermon_limit = isset($attributes['sermonLimit']) ? (int) $attributes['sermonLimit'] : 5;
$layout = isset($attributes['layout']) ? sanitize_text_field($attributes['layout']) : 'horizontal';

// Early exit if no profile selected.
if (!$profile_id) {
    return;
}

// Fetch profile data based on type.
$profile = null;
$profile_url = '';

if ($profile_type === 'preacher') {
    $profile = \SermonBrowser\Facades\Preacher::find($profile_id);
    if ($profile) {
        $profile_url = \SermonBrowser\Frontend\UrlBuilder::build(['preacher' => $profile_id]);
    }
} else {
    $profile = \SermonBrowser\Facades\Series::find($profile_id);
    if ($profile) {
        $profile_url = \SermonBrowser\Frontend\UrlBuilder::build(['series' => $profile_id]);
    }
}

// Exit if profile not found.
if (!$profile) {
    return;
}

// Fetch recent sermons for this profile.
$sermons = [];
if ($show_sermons) {
    if ($profile_type === 'preacher') {
        $sermons = \SermonBrowser\Facades\Sermon::findByPreacher($profile_id, $sermon_limit);
    } else {
        $sermons = \SermonBrowser\Facades\Sermon::findBySeries($profile_id, $sermon_limit);
    }
}

// Get image URL if available.
$image_url = '';
if ($show_image && !empty($profile->image)) {
    $upload_url = \SermonBrowser\Config\OptionsManager::get('upload_url');
    $image_url = $upload_url . $profile->image;
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-profile-block sb-profile-block--' . esc_attr($layout),
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <div class="sb-profile-block__content">
        <?php if ($show_image && $image_url) : ?>
            <div class="sb-profile-block__image">
                <a href="<?php echo esc_url($profile_url); ?>">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($profile->name); ?>">
                </a>
            </div>
        <?php endif; ?>

        <div class="sb-profile-block__info">
            <h3 class="sb-profile-block__name">
                <a href="<?php echo esc_url($profile_url); ?>">
                    <?php echo esc_html($profile->name); ?>
                </a>
            </h3>

            <?php if ($show_bio && !empty($profile->description)) : ?>
                <div class="sb-profile-block__bio">
                    <?php echo wp_kses_post($profile->description); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_sermons && !empty($sermons)) : ?>
                <div class="sb-profile-block__sermons">
                    <h4><?php esc_html_e('Recent Sermons', 'sermon-browser'); ?></h4>
                    <ul>
                        <?php foreach ($sermons as $sermon) : ?>
                            <li>
                                <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $sermon->id])); ?>">
                                    <?php echo esc_html($sermon->title); ?>
                                </a>
                                <?php if (!empty($sermon->datetime)) : ?>
                                    <span class="sb-profile-block__sermon-date">
                                        <?php echo esc_html(wp_date(get_option('date_format'), strtotime($sermon->datetime))); ?>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
