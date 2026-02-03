<?php

/**
 * Server-side rendering of the sermon-browser/sermon-media block.
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

$sermon_id = isset($attributes['sermonId']) ? (int) $attributes['sermonId'] : 0;
$use_latest = isset($attributes['useLatest']) ? (bool) $attributes['useLatest'] : false;
$media_type = $attributes['mediaType'] ?? 'audio';
$show_download = $attributes['showDownload'] ?? true;
$player_style = $attributes['playerStyle'] ?? 'default';
$autoplay = $attributes['autoplay'] ?? false;
$show_title = $attributes['showTitle'] ?? false;
$show_meta = $attributes['showMeta'] ?? false;

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

// Categorize files by type.
$audio_extensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
$video_extensions = ['mp4', 'webm', 'ogv', 'mov', 'avi'];

$audio_files = [];
$video_files = [];

foreach ($files as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, $audio_extensions, true)) {
        $audio_files[] = $file;
    } elseif (in_array($ext, $video_extensions, true)) {
        $video_files[] = $file;
    }
}

$upload_url = \SermonBrowser\Config\OptionsManager::get('upload_url');

// Determine what to display.
$show_audio = ($media_type === 'audio' || $media_type === 'both') && !empty($audio_files);
$show_video = ($media_type === 'video' || $media_type === 'both') && !empty($video_files);

if (!$show_audio && !$show_video) {
    ?>
    <div <?php echo get_block_wrapper_attributes(['class' => 'sb-sermon-media']); ?>>
        <p class="sb-sermon-media__no-media">
            <?php esc_html_e('No media files available for this sermon.', 'sermon-browser'); ?>
        </p>
    </div>
    <?php
    return;
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-sermon-media sb-sermon-media--' . esc_attr($player_style),
]);

// MIME types for audio.
$audio_mime = [
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'm4a' => 'audio/mp4',
    'aac' => 'audio/aac',
    'flac' => 'audio/flac',
];

// MIME types for video.
$video_mime = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogv' => 'video/ogg',
    'mov' => 'video/quicktime',
    'avi' => 'video/x-msvideo',
];

// Build autoplay attribute.
$autoplay_attr = $autoplay ? ' autoplay muted' : '';
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($show_title) : ?>
        <h3 class="sb-sermon-media__title">
            <a href="<?php echo esc_url(\SermonBrowser\Frontend\UrlBuilder::build(['sermon_id' => $sermon->id])); ?>">
                <?php echo esc_html($sermon->title); ?>
            </a>
        </h3>
    <?php endif; ?>

    <?php if ($show_meta) : ?>
        <div class="sb-sermon-media__meta">
            <?php if (!empty($sermon->datetime)) : ?>
                <span class="sb-sermon-media__date">
                    <?php echo esc_html(wp_date(get_option('date_format'), strtotime($sermon->datetime))); ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($sermon->preacher)) : ?>
                <span class="sb-sermon-media__preacher">
                    <?php echo esc_html($sermon->preacher); ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($show_video) : ?>
        <div class="sb-sermon-media__video">
            <?php
            $primary_video = $video_files[0];
            $video_url = $upload_url . $primary_video;
            $video_ext = strtolower(pathinfo($primary_video, PATHINFO_EXTENSION));
            $video_type = $video_mime[$video_ext] ?? 'video/mp4';
            ?>
            <video controls class="sb-sermon-media__video-element"<?php echo $autoplay_attr; ?>>
                <source src="<?php echo esc_url($video_url); ?>" type="<?php echo esc_attr($video_type); ?>">
                <?php esc_html_e('Your browser does not support the video element.', 'sermon-browser'); ?>
            </video>
        </div>
    <?php endif; ?>

    <?php if ($show_audio) : ?>
        <div class="sb-sermon-media__audio">
            <?php
            $primary_audio = $audio_files[0];
            $audio_url = $upload_url . $primary_audio;
            $audio_ext = strtolower(pathinfo($primary_audio, PATHINFO_EXTENSION));
            $audio_type = $audio_mime[$audio_ext] ?? 'audio/mpeg';
            ?>
            <audio controls class="sb-sermon-media__audio-element"<?php echo $autoplay_attr; ?>>
                <source src="<?php echo esc_url($audio_url); ?>" type="<?php echo esc_attr($audio_type); ?>">
                <?php esc_html_e('Your browser does not support the audio element.', 'sermon-browser'); ?>
            </audio>
        </div>
    <?php endif; ?>

    <?php if ($show_download) : ?>
        <div class="sb-sermon-media__downloads">
            <?php if ($show_video) : ?>
                <?php foreach ($video_files as $video_file) : ?>
                    <a href="<?php echo esc_url($upload_url . $video_file); ?>" download class="sb-sermon-media__download-link sb-sermon-media__download-link--video">
                        <span class="dashicons dashicons-video-alt3"></span>
                        <?php
                        /* translators: %s: file extension in uppercase (e.g., MP4) */
                        printf(esc_html__('Download %s', 'sermon-browser'), strtoupper(pathinfo($video_file, PATHINFO_EXTENSION)));
                        ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($show_audio) : ?>
                <?php foreach ($audio_files as $audio_file) : ?>
                    <a href="<?php echo esc_url($upload_url . $audio_file); ?>" download class="sb-sermon-media__download-link sb-sermon-media__download-link--audio">
                        <span class="dashicons dashicons-download"></span>
                        <?php
                        /* translators: %s: file extension in uppercase (e.g., MP3) */
                        printf(esc_html__('Download %s', 'sermon-browser'), strtoupper(pathinfo($audio_file, PATHINFO_EXTENSION)));
                        ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
