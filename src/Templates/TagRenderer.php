<?php

/**
 * Tag Renderer for template tag replacement.
 *
 * Provides methods to render individual template tags like [sermon_title],
 * [preacher_link], etc. without using eval(). Each tag has a dedicated
 * method that returns safe, escaped HTML output.
 *
 * @package SermonBrowser\Templates
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Templates;

use stdClass;

/**
 * Class TagRenderer
 *
 * Renders individual template tags for sermon browser templates.
 * Replaces the eval()-based dictionary.php approach with safe method calls.
 */
class TagRenderer
{
    /**
     * List of available tags and their render methods.
     *
     * @var array<string, string>
     */
    private array $tagMethods = [
        // Search context tags
        'filters_form' => 'renderFiltersForm',
        'most_popular' => 'renderMostPopular',
        'tag_cloud' => 'renderTagCloud',
        'sermons_count' => 'renderSermonsCount',
        'sermons_loop' => 'renderSermonsLoopStart',
        '/sermons_loop' => 'renderSermonsLoopEnd',
        'sermon_title' => 'renderSermonTitle',
        'preacher_link' => 'renderPreacherLink',
        'series_link' => 'renderSeriesLink',
        'service_link' => 'renderServiceLink',
        'date' => 'renderDate',
        'first_passage' => 'renderFirstPassage',
        'files_loop' => 'renderFilesLoopStart',
        '/files_loop' => 'renderFilesLoopEnd',
        'file' => 'renderFile',
        'file_with_download' => 'renderFileWithDownload',
        'embed_loop' => 'renderEmbedLoopStart',
        '/embed_loop' => 'renderEmbedLoopEnd',
        'embed' => 'renderEmbed',
        'next_page' => 'renderNextPage',
        'previous_page' => 'renderPreviousPage',
        'podcast_for_search' => 'renderPodcastForSearch',
        'podcast' => 'renderPodcast',
        'itunes_podcast' => 'renderItunesPodcast',
        'itunes_podcast_for_search' => 'renderItunesPodcastForSearch',
        'podcasticon' => 'renderPodcastIcon',
        'podcasticon_for_search' => 'renderPodcastIconForSearch',
        'editlink' => 'renderEditLink',
        'creditlink' => 'renderCreditLink',

        // Single sermon context tags
        'sermon_description' => 'renderSermonDescription',
        'preacher_description' => 'renderPreacherDescription',
        'preacher_image' => 'renderPreacherImage',
        'passages_loop' => 'renderPassagesLoopStart',
        '/passages_loop' => 'renderPassagesLoopEnd',
        'passage' => 'renderPassage',
        'next_sermon' => 'renderNextSermon',
        'prev_sermon' => 'renderPrevSermon',
        'sameday_sermon' => 'renderSamedaySermon',
        'tags' => 'renderTags',
        'biblepassage' => 'renderBiblePassage',

        // Bible translation tags
        'esvtext' => 'renderEsvText',
        'kjvtext' => 'renderKjvText',
        'asvtext' => 'renderAsvText',
        'nettext' => 'renderNetText',
        'ylttext' => 'renderYltText',
        'webtext' => 'renderWebText',
        'akjvtext' => 'renderAkjvText',
        'hnvtext' => 'renderHnvText',
        'lbrvtext' => 'renderLbrvText',
        'cornilescutext' => 'renderCornilescuText',
        'synodaltext' => 'renderSynodalText',
    ];

    /**
     * Get list of available tag names.
     *
     * @return array<string> List of tag names.
     */
    public function getAvailableTags(): array
    {
        return array_keys($this->tagMethods);
    }

    // =========================================================================
    // Sermon Title and Description Tags
    // =========================================================================

    /**
     * Render the sermon title tag.
     *
     * In search context, returns a link to the sermon.
     * In single context, returns just the title text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context ('search' or 'single').
     * @return string The rendered HTML.
     */
    public function renderSermonTitle(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        $title = stripslashes((string) $sermon->title);
        $title = esc_html($title);

        if ($context === 'search') {
            $url = sb_build_url(['sermon_id' => $sermon->id], true);
            return '<a href="' . esc_url($url) . '">' . $title . '</a>';
        }

        return $title;
    }

    /**
     * Render the sermon description tag.
     *
     * Returns the description with WordPress auto-paragraph formatting.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context ('search' or 'single').
     * @return string The rendered HTML.
     */
    public function renderSermonDescription(?object $sermon, string $context): string
    {
        if ($sermon === null || empty($sermon->description)) {
            return '';
        }

        $description = stripslashes((string) $sermon->description);
        return wpautop($description);
    }

    // =========================================================================
    // Preacher Tags
    // =========================================================================

    /**
     * Render the preacher link tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderPreacherLink(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        $name = stripslashes((string) $sermon->preacher);
        $url = sb_build_url(['preacher' => $sermon->pid], false);

        return '<a href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
    }

    /**
     * Render the preacher description tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderPreacherDescription(?object $sermon, string $context): string
    {
        if ($sermon === null || empty($sermon->preacher_description)) {
            return '';
        }

        $preacherName = stripslashes((string) $sermon->preacher);
        $description = stripslashes((string) $sermon->preacher_description);

        return '<div class="preacher-description">'
            . '<span class="about">' . esc_html__('About', 'sermon-browser') . ' '
            . esc_html($preacherName) . ': </span>'
            . '<span class="description">' . esc_html($description) . '</span>'
            . '</div>';
    }

    /**
     * Render the preacher image tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderPreacherImage(?object $sermon, string $context): string
    {
        if ($sermon === null || empty($sermon->image)) {
            return '';
        }

        $uploadDir = sb_get_option('upload_dir');
        $siteUrl = trailingslashit(site_url());
        $imageSrc = $siteUrl . $uploadDir . 'images/' . $sermon->image;
        $alt = stripslashes((string) $sermon->preacher);

        return '<img alt="' . esc_attr($alt) . '" class="preacher" src="' . esc_url($imageSrc) . '">';
    }

    // =========================================================================
    // Series and Service Tags
    // =========================================================================

    /**
     * Render the series link tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderSeriesLink(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        $name = stripslashes((string) $sermon->series);
        $url = sb_build_url(['series' => $sermon->ssid], false);

        return '<a href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
    }

    /**
     * Render the service link tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderServiceLink(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        $name = stripslashes((string) $sermon->service);
        $url = sb_build_url(['service' => $sermon->sid], false);

        return '<a href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
    }

    // =========================================================================
    // Date and Passage Tags
    // =========================================================================

    /**
     * Render the date tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The formatted date.
     */
    public function renderDate(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        return sb_formatted_date($sermon);
    }

    /**
     * Render the first passage tag (used in search results).
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The formatted passage reference.
     */
    public function renderFirstPassage(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        $start = is_string($sermon->start) ? unserialize($sermon->start) : $sermon->start;
        $end = is_string($sermon->end) ? unserialize($sermon->end) : $sermon->end;

        if (empty($start) || empty($end) || !isset($start[0]) || !isset($end[0])) {
            return '';
        }

        return sb_get_books($start[0], $end[0]);
    }

    // =========================================================================
    // Pagination Tags
    // =========================================================================

    /**
     * Render the next page link tag.
     *
     * Note: This function outputs directly, so we capture with ob_start.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderNextPage(mixed $data, string $context): string
    {
        ob_start();
        sb_print_next_page_link();
        return ob_get_clean() ?: '';
    }

    /**
     * Render the previous page link tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderPreviousPage(mixed $data, string $context): string
    {
        ob_start();
        sb_print_prev_page_link();
        return ob_get_clean() ?: '';
    }

    // =========================================================================
    // Podcast Tags
    // =========================================================================

    /**
     * Render the podcast URL tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The podcast URL.
     */
    public function renderPodcast(mixed $data, string $context): string
    {
        return sb_get_option('podcast_url');
    }

    /**
     * Render the podcast for search URL tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The custom podcast URL.
     */
    public function renderPodcastForSearch(mixed $data, string $context): string
    {
        return sb_podcast_url();
    }

    /**
     * Render the iTunes podcast URL tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The iTunes podcast URL.
     */
    public function renderItunesPodcast(mixed $data, string $context): string
    {
        $url = sb_get_option('podcast_url');
        return str_replace('http://', 'itpc://', $url);
    }

    /**
     * Render the iTunes podcast for search URL tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The iTunes custom podcast URL.
     */
    public function renderItunesPodcastForSearch(mixed $data, string $context): string
    {
        $url = sb_podcast_url();
        return str_replace('http://', 'itpc://', $url);
    }

    /**
     * Render the podcast icon tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderPodcastIcon(mixed $data, string $context): string
    {
        $iconUrl = SB_PLUGIN_URL . '/assets/images/icons/podcast.png';
        return '<img alt="Subscribe to full podcast" title="Subscribe to full podcast" '
            . 'class="podcasticon" src="' . esc_url($iconUrl) . '"/>';
    }

    /**
     * Render the podcast icon for search tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderPodcastIconForSearch(mixed $data, string $context): string
    {
        $iconUrl = SB_PLUGIN_URL . '/assets/images/icons/podcast_custom.png';
        return '<img alt="Subscribe to custom podcast" title="Subscribe to custom podcast" '
            . 'class="podcasticon" src="' . esc_url($iconUrl) . '"/>';
    }

    // =========================================================================
    // Credit and Edit Link Tags
    // =========================================================================

    /**
     * Render the credit link tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderCreditLink(mixed $data, string $context): string
    {
        return '<div id="poweredbysermonbrowser">Powered by Sermon Browser</div>';
    }

    /**
     * Render the edit link tag.
     *
     * Note: This function outputs directly, so we capture with ob_start.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderEditLink(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        $id = $context === 'single' && isset($sermon->id) ? $sermon->id : ($sermon->id ?? 0);

        ob_start();
        sb_edit_link($id);
        return ob_get_clean() ?: '';
    }

    // =========================================================================
    // Tags Tag
    // =========================================================================

    /**
     * Render the tags tag.
     *
     * @param array<string>|object $tagsOrSermon The tags array or sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderTags(array|object $tagsOrSermon, string $context): string
    {
        $tags = is_array($tagsOrSermon) ? $tagsOrSermon : [];

        if (empty($tags)) {
            return '';
        }

        $output = [];
        foreach ($tags as $tag) {
            $tag = stripslashes((string) $tag);
            $url = sb_build_url(['stag' => $tag], false);
            $output[] = '<a href="' . esc_url($url) . '">' . esc_html($tag) . '</a>';
        }

        return implode(', ', $output);
    }

    // =========================================================================
    // Navigation Link Tags
    // =========================================================================

    /**
     * Render the next sermon link tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderNextSermon(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        ob_start();
        sb_print_next_sermon_link($sermon);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the previous sermon link tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderPrevSermon(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        ob_start();
        sb_print_prev_sermon_link($sermon);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the same day sermon link tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderSamedaySermon(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        ob_start();
        sb_print_sameday_sermon_link($sermon);
        return ob_get_clean() ?: '';
    }

    // =========================================================================
    // Bible Translation Tags
    // =========================================================================

    /**
     * Render bible text for a given translation.
     *
     * @param object|null $sermon The sermon object.
     * @param string $version The bible version code.
     * @return string The rendered HTML.
     */
    public function renderBibleText(?object $sermon, string $version): string
    {
        if ($sermon === null) {
            return '';
        }

        $start = is_array($sermon->start) ? $sermon->start : [];
        $end = is_array($sermon->end) ? $sermon->end : [];

        if (empty($start) || empty($end)) {
            return '';
        }

        $output = '';
        for ($i = 0; $i < count($start); $i++) {
            if (isset($start[$i]) && isset($end[$i])) {
                $output .= sb_add_bible_text($start[$i], $end[$i], $version);
            }
        }

        return $output;
    }

    /**
     * Render ESV bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderEsvText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'esv');
    }

    /**
     * Render KJV bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderKjvText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'kjv');
    }

    /**
     * Render ASV bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderAsvText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'asv');
    }

    /**
     * Render NET bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderNetText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'net');
    }

    /**
     * Render YLT bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderYltText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'ylt');
    }

    /**
     * Render WEB bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderWebText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'web');
    }

    /**
     * Render AKJV bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderAkjvText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'akjv');
    }

    /**
     * Render HNV bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderHnvText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'hnv');
    }

    /**
     * Render LBRV (RV1909) bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderLbrvText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'rv1909');
    }

    /**
     * Render Cornilescu bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderCornilescuText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'cornilescu');
    }

    /**
     * Render Synodal bible text.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderSynodalText(?object $sermon, string $context): string
    {
        return $this->renderBibleText($sermon, 'synodal');
    }

    /**
     * Render bible passage tag.
     *
     * @param object|null $sermon The sermon object.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderBiblePassage(?object $sermon, string $context): string
    {
        if ($sermon === null) {
            return '';
        }

        $start = is_array($sermon->start) ? $sermon->start : [];
        $end = is_array($sermon->end) ? $sermon->end : [];

        if (empty($start) || empty($end)) {
            return '';
        }

        ob_start();
        for ($i = 0; $i < count($start); $i++) {
            if (isset($start[$i]) && isset($end[$i])) {
                sb_print_bible_passage($start[$i], $end[$i]);
            }
        }
        return ob_get_clean() ?: '';
    }

    // =========================================================================
    // Loop Marker Tags (for TagParser coordination)
    // =========================================================================

    /**
     * Render sermons loop start marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderSermonsLoopStart(mixed $data, string $context): string
    {
        return '{{SERMONS_LOOP_START}}';
    }

    /**
     * Render sermons loop end marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderSermonsLoopEnd(mixed $data, string $context): string
    {
        return '{{SERMONS_LOOP_END}}';
    }

    /**
     * Render files loop start marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderFilesLoopStart(mixed $data, string $context): string
    {
        return '{{FILES_LOOP_START}}';
    }

    /**
     * Render files loop end marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderFilesLoopEnd(mixed $data, string $context): string
    {
        return '{{FILES_LOOP_END}}';
    }

    /**
     * Render embed loop start marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderEmbedLoopStart(mixed $data, string $context): string
    {
        return '{{EMBED_LOOP_START}}';
    }

    /**
     * Render embed loop end marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderEmbedLoopEnd(mixed $data, string $context): string
    {
        return '{{EMBED_LOOP_END}}';
    }

    /**
     * Render passages loop start marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderPassagesLoopStart(mixed $data, string $context): string
    {
        return '{{PASSAGES_LOOP_START}}';
    }

    /**
     * Render passages loop end marker.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The marker string.
     */
    public function renderPassagesLoopEnd(mixed $data, string $context): string
    {
        return '{{PASSAGES_LOOP_END}}';
    }

    // =========================================================================
    // Filter Form and Complex Tags
    // =========================================================================

    /**
     * Render the filters form tag.
     *
     * @param array<string, mixed>|null $atts Filter attributes.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderFiltersForm(array|null $atts, string $context): string
    {
        if ($atts === null) {
            $atts = [];
        }

        ob_start();
        sb_print_filters($atts);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the most popular tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderMostPopular(mixed $data, string $context): string
    {
        ob_start();
        sb_print_most_popular();
        return ob_get_clean() ?: '';
    }

    /**
     * Render the tag cloud tag.
     *
     * @param mixed $data Not used for this tag.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderTagCloud(mixed $data, string $context): string
    {
        ob_start();
        sb_print_tag_clouds();
        return ob_get_clean() ?: '';
    }

    /**
     * Render the sermons count tag.
     *
     * @param int|mixed $count The sermon count.
     * @param string $context The context.
     * @return string The count as string.
     */
    public function renderSermonsCount(mixed $count, string $context): string
    {
        return (string) $count;
    }

    // =========================================================================
    // File and Embed Tags
    // =========================================================================

    /**
     * Render the file tag.
     *
     * @param string|mixed $mediaName The media file name.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderFile(mixed $mediaName, string $context): string
    {
        if (empty($mediaName)) {
            return '';
        }

        ob_start();
        sb_print_url((string) $mediaName);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the file with download tag.
     *
     * @param string|mixed $mediaName The media file name.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderFileWithDownload(mixed $mediaName, string $context): string
    {
        if (empty($mediaName)) {
            return '';
        }

        ob_start();
        sb_print_url_link((string) $mediaName);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the embed tag.
     *
     * @param string|mixed $mediaName The base64-encoded embed code.
     * @param string $context The context.
     * @return string The rendered HTML.
     */
    public function renderEmbed(mixed $mediaName, string $context): string
    {
        if (empty($mediaName)) {
            return '';
        }

        $decoded = base64_decode((string) $mediaName);
        return do_shortcode($decoded);
    }

    // =========================================================================
    // Passage Tags
    // =========================================================================

    /**
     * Render a single passage reference.
     *
     * @param array<string, mixed> $start The start reference.
     * @param array<string, mixed> $end The end reference.
     * @return string The formatted passage reference.
     */
    public function renderPassage(array $start, array $end): string
    {
        return sb_get_books($start, $end);
    }
}
