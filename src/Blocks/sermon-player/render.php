<?php

/**
 * Server-side rendering of the sermon-browser/sermon-player block.
 *
 * @package sermon-browser
 * @since 0.7.0
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

$sermon_id = isset($attributes['sermonId']) ? (int) $attributes['sermonId'] : 0;
$use_latest = isset($attributes['useLatest']) ? (bool) $attributes['useLatest'] : false;
$show_title = isset($attributes['showTitle']) ? (bool) $attributes['showTitle'] : true;
$show_preacher = isset($attributes['showPreacher']) ? (bool) $attributes['showPreacher'] : true;
$show_date = isset($attributes['showDate']) ? (bool) $attributes['showDate'] : true;
$show_download = isset($attributes['showDownload']) ? (bool) $attributes['showDownload'] : true;

// If useLatest, fetch the latest sermon ID.
if ($use_latest) {
    $latest = sb_get_sermons([], ['by' => 'm.datetime', 'dir' => 'desc'], 1, 1);
    if (!empty($latest) && isset($latest[0]->id)) {
        $sermon_id = (int) $latest[0]->id;
    }
}

if (!$sermon_id) {
    return;
}

// Get sermon data.
$sermon_data = sb_get_single_sermon($sermon_id);

if (!$sermon_data || empty($sermon_data['Sermon'])) {
    return;
}

$sermon = $sermon_data['Sermon'];
$files = $sermon_data['Files'] ?? [];

// Find audio files.
$audio_extensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
$audio_files = [];

foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, $audio_extensions, true)) {
        $audio_files[] = $file;
    }
}

// If no audio files, don't render the block.
if (empty($audio_files)) {
    ?>
    <div <?php echo get_block_wrapper_attributes(['class' => 'sb-sermon-player']); ?>>
        <p class="sb-sermon-player__no-audio">
            <?php esc_html_e('No audio files available for this sermon.', 'sermon-browser'); ?>
        </p>
    </div>
    <?php
    return;
}

$upload_url = \SermonBrowser\Config\OptionsManager::get('upload_url');
$primary_audio = $audio_files[0];
$audio_url = $upload_url . $primary_audio;

// Determine MIME type.
$ext = strtolower(pathinfo($primary_audio, PATHINFO_EXTENSION));
$mime_types = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4',
    'aac' => 'audio/aac',
    'flac' => 'audio/flac',
];
$mime_type = $mime_types[$ext] ?? 'audio/mpeg';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-sermon-player',
]);

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($show_title) : ?>
        <h3 class="sb-sermon-player__title">
            <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $sermon->id])); ?>">
                <?php echo esc_html($sermon->title); ?>
            </a>
        </h3>
    <?php endif; ?>

    <?php if ($show_date || $show_preacher) : ?>
        <div class="sb-sermon-player__meta">
            <?php if ($show_date && !empty($sermon->datetime)) : ?>
                <span class="sb-sermon-player__date">
                    <?php echo esc_html(wp_date(get_option('date_format'), strtotime($sermon->datetime))); ?>
                </span>
            <?php endif; ?>

            <?php if ($show_preacher && !empty($sermon->preacher)) : ?>
                <span class="sb-sermon-player__preacher">
                    <?php echo esc_html($sermon->preacher); ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="sb-sermon-player__audio">
        <audio controls class="sb-sermon-player__audio-element">
            <source src="<?php echo esc_url($audio_url); ?>" type="<?php echo esc_attr($mime_type); ?>">
            <?php esc_html_e('Your browser does not support the audio element.', 'sermon-browser'); ?>
        </audio>
    </div>

    <?php if ($show_download) : ?>
        <div class="sb-sermon-player__download">
            <a href="<?php echo esc_url($audio_url); ?>" download class="sb-sermon-player__download-link">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Download Audio', 'sermon-browser'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
