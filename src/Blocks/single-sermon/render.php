<?php

/**
 * Server-side rendering of the sermon-browser/single-sermon block.
 *
 * @package sermon-browser
 * @since 0.6.0
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
$show_description = isset($attributes['showDescription']) ? (bool) $attributes['showDescription'] : true;
$show_preacher = isset($attributes['showPreacher']) ? (bool) $attributes['showPreacher'] : true;
$show_date = isset($attributes['showDate']) ? (bool) $attributes['showDate'] : true;
$show_series = isset($attributes['showSeries']) ? (bool) $attributes['showSeries'] : true;
$show_passage = isset($attributes['showPassage']) ? (bool) $attributes['showPassage'] : true;
$show_media = isset($attributes['showMedia']) ? (bool) $attributes['showMedia'] : true;
$show_tags = isset($attributes['showTags']) ? (bool) $attributes['showTags'] : true;

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
$code = $sermon_data['Code'] ?? [];
$tags = $sermon_data['Tags'] ?? [];

// Build display options for template.
$display_options = [
    'show_description' => $show_description,
    'show_preacher' => $show_preacher,
    'show_date' => $show_date,
    'show_series' => $show_series,
    'show_passage' => $show_passage,
    'show_media' => $show_media,
    'show_tags' => $show_tags,
];

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sb-single-sermon',
]);

// Use TemplateEngine if available, otherwise fallback to direct output.
try {
    $engine = new \SermonBrowser\Templates\TemplateEngine();
    $html = $engine->render('single', [
        'Sermon' => $sermon,
        'Files' => $files,
        'Code' => $code,
        'Tags' => $tags,
        'display_options' => $display_options,
    ]);
    ?>
    <div <?php echo $wrapper_attributes; ?>>
        <?php echo $html; ?>
    </div>
    <?php
} catch (\Exception $e) {
    // Fallback: render basic sermon info.
    ?>
    <div <?php echo $wrapper_attributes; ?>>
        <article class="sb-single-sermon__content">
            <h2 class="sb-single-sermon__title"><?php echo esc_html($sermon->title); ?></h2>

            <?php if ($show_date && !empty($sermon->datetime)) : ?>
                <p class="sb-single-sermon__date">
                    <?php echo esc_html(wp_date(get_option('date_format'), strtotime($sermon->datetime))); ?>
                </p>
            <?php endif; ?>

            <?php if ($show_preacher && !empty($sermon->preacher)) : ?>
                <p class="sb-single-sermon__preacher">
                    <?php echo esc_html($sermon->preacher); ?>
                </p>
            <?php endif; ?>

            <?php if ($show_series && !empty($sermon->series)) : ?>
                <p class="sb-single-sermon__series">
                    <?php esc_html_e('Series:', 'sermon-browser'); ?>
                    <?php echo esc_html($sermon->series); ?>
                </p>
            <?php endif; ?>

            <?php if ($show_passage && !empty($sermon->start)) : ?>
                <p class="sb-single-sermon__passage">
                    <?php echo esc_html(sb_print_passage($sermon, true)); ?>
                </p>
            <?php endif; ?>

            <?php if ($show_description && !empty($sermon->description)) : ?>
                <div class="sb-single-sermon__description">
                    <?php echo wp_kses_post($sermon->description); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_media && !empty($files)) : ?>
                <div class="sb-single-sermon__media">
                    <?php foreach ($files as $file) : ?>
                        <?php
                        $file_url = \SermonBrowser\Config\OptionsManager::get('upload_url') . $file;
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        ?>
                        <?php if (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'])) : ?>
                            <audio controls class="sb-single-sermon__audio">
                                <source src="<?php echo esc_url($file_url); ?>" type="audio/<?php echo esc_attr($ext === 'm4a' ? 'mp4' : $ext); ?>">
                            </audio>
                        <?php else : ?>
                            <a href="<?php echo esc_url($file_url); ?>" class="sb-single-sermon__file">
                                <?php echo esc_html($file); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($show_tags && !empty($tags)) : ?>
                <div class="sb-single-sermon__tags">
                    <?php foreach ($tags as $tag) : ?>
                        <a href="<?php echo esc_url(sb_get_tag_link($tag)); ?>" class="sb-single-sermon__tag">
                            <?php echo esc_html($tag); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </div>
    <?php
}
