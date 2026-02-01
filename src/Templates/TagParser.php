<?php

/**
 * Tag Parser for regex-based template tag replacement.
 *
 * Parses templates with [tag_name] patterns and handles loop constructs.
 * Uses TagRenderer for individual tag rendering.
 *
 * @package SermonBrowser\Templates
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Templates;

use stdClass;

/**
 * Class TagParser
 *
 * Parses template strings and replaces tags with rendered content.
 * Handles loop markers from TagRenderer for sermons, files, embeds, and passages.
 */
class TagParser
{
    /**
     * The tag renderer instance.
     *
     * @var TagRenderer
     */
    private TagRenderer $renderer;

    /**
     * Loop marker patterns.
     */
    private const SERMONS_LOOP_START = '{{SERMONS_LOOP_START}}';
    private const SERMONS_LOOP_END = '{{SERMONS_LOOP_END}}';
    private const FILES_LOOP_START = '{{FILES_LOOP_START}}';
    private const FILES_LOOP_END = '{{FILES_LOOP_END}}';
    private const EMBED_LOOP_START = '{{EMBED_LOOP_START}}';
    private const EMBED_LOOP_END = '{{EMBED_LOOP_END}}';
    private const PASSAGES_LOOP_START = '{{PASSAGES_LOOP_START}}';
    private const PASSAGES_LOOP_END = '{{PASSAGES_LOOP_END}}';

    /**
     * Constructor.
     *
     * @param TagRenderer|null $renderer The tag renderer instance.
     */
    public function __construct(?TagRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new TagRenderer();
    }

    /**
     * Parse a template string and replace tags with rendered content.
     *
     * @param string $template The template string with [tag_name] patterns.
     * @param array<string, mixed> $data The data array containing sermons, media, etc.
     * @param string $context The context ('search' or 'single').
     * @return string The rendered HTML.
     */
    public function parse(string $template, array $data, string $context): string
    {
        if ($template === '') {
            return '';
        }

        // Step 1: Convert loop tags to markers
        $result = $this->convertLoopTagsToMarkers($template, $context);

        // Step 2: Process loop markers (replaces tags within loops)
        $result = $this->processLoops($result, $data, $context);

        // Step 3: Replace any remaining tags outside loops
        $result = $this->replaceTags($result, $data, $context, null, null, null);

        return $result;
    }

    /**
     * Convert loop tags [sermons_loop], [files_loop], etc. to markers.
     *
     * @param string $template The template string.
     * @param string $context The context.
     * @return string The template with loop tags converted to markers.
     */
    private function convertLoopTagsToMarkers(string $template, string $_context): string
    {
        $loopTags = [
            'sermons_loop' => self::SERMONS_LOOP_START,
            '/sermons_loop' => self::SERMONS_LOOP_END,
            'files_loop' => self::FILES_LOOP_START,
            '/files_loop' => self::FILES_LOOP_END,
            'embed_loop' => self::EMBED_LOOP_START,
            '/embed_loop' => self::EMBED_LOOP_END,
            'passages_loop' => self::PASSAGES_LOOP_START,
            '/passages_loop' => self::PASSAGES_LOOP_END,
        ];

        foreach ($loopTags as $tag => $marker) {
            $template = str_replace('[' . $tag . ']', $marker, $template);
        }

        return $template;
    }

    /**
     * Replace all [tag_name] patterns with rendered content.
     *
     * @param string $template The template string.
     * @param array<string, mixed> $data The data array.
     * @param string $context The context.
     * @param object|null $currentSermon Optional sermon for loop iteration.
     * @param object|null $currentMedia Optional media for loop iteration.
     * @param array<string, mixed>|null $currentPassage Optional passage for loop iteration.
     * @return string The template with tags replaced.
     */
    private function replaceTags(
        string $template,
        array $data,
        string $context,
        ?object $currentSermon = null,
        ?object $currentMedia = null,
        ?array $currentPassage = null
    ): string {
        $availableTags = $this->renderer->getAvailableTags();

        // Build regex pattern for all tags
        $pattern = '/\[([a-z_\/]+)\]/';

        return preg_replace_callback(
            $pattern,
            function ($matches) use ($data, $context, $availableTags, $currentSermon, $currentMedia, $currentPassage) {
                $tagName = $matches[1];

                // Check if it's a known tag
                if (!in_array($tagName, $availableTags, true)) {
                    // Unknown tag - preserve as-is
                    return $matches[0];
                }

                return $this->renderTag($tagName, $data, $context, $currentSermon, $currentMedia, $currentPassage);
            },
            $template
        ) ?? $template;
    }

    /**
     * Render a single tag.
     *
     * @param string $tagName The tag name (without brackets).
     * @param array<string, mixed> $data The data array.
     * @param string $context The context.
     * @param object|null $currentSermon Current sermon in loop iteration.
     * @param object|null $currentMedia Current media in loop iteration.
     * @param array<string, mixed>|null $currentPassage Current passage in loop iteration.
     * @return string The rendered content.
     */
    private function renderTag(
        string $tagName,
        array $data,
        string $context,
        ?object $currentSermon = null,
        ?object $currentMedia = null,
        ?array $currentPassage = null
    ): string {
        // Determine the sermon object to use
        $sermon = $currentSermon ?? ($data['Sermon'] ?? null);

        // Map tag names to renderer methods with appropriate data
        switch ($tagName) {
            // Loop markers - these should already be converted, but handle just in case
            case 'sermons_loop':
                return self::SERMONS_LOOP_START;
            case '/sermons_loop':
                return self::SERMONS_LOOP_END;
            case 'files_loop':
                return self::FILES_LOOP_START;
            case '/files_loop':
                return self::FILES_LOOP_END;
            case 'embed_loop':
                return self::EMBED_LOOP_START;
            case '/embed_loop':
                return self::EMBED_LOOP_END;
            case 'passages_loop':
                return self::PASSAGES_LOOP_START;
            case '/passages_loop':
                return self::PASSAGES_LOOP_END;

            // Sermon-related tags
            case 'sermon_title':
                return $this->renderer->renderSermonTitle($sermon, $context);
            case 'sermon_description':
                return $this->renderer->renderSermonDescription($sermon, $context);
            case 'preacher_link':
                return $this->renderer->renderPreacherLink($sermon, $context);
            case 'preacher_description':
                return $this->renderer->renderPreacherDescription($sermon, $context);
            case 'preacher_image':
                return $this->renderer->renderPreacherImage($sermon, $context);
            case 'series_link':
                return $this->renderer->renderSeriesLink($sermon, $context);
            case 'service_link':
                return $this->renderer->renderServiceLink($sermon, $context);
            case 'date':
                return $this->renderer->renderDate($sermon, $context);
            case 'first_passage':
                return $this->renderer->renderFirstPassage($sermon, $context);
            case 'editlink':
                return $this->renderer->renderEditLink($sermon, $context);

            // Tags that use the Tags array
            case 'tags':
                $tags = $data['Tags'] ?? [];
                return $this->renderer->renderTags($tags, $context);

            // Data-driven tags
            case 'sermons_count':
                $count = $data['record_count'] ?? 0;
                return $this->renderer->renderSermonsCount($count, $context);
            case 'filters_form':
                $atts = $data['atts'] ?? [];
                return $this->renderer->renderFiltersForm($atts, $context);

            // Navigation tags
            case 'next_page':
                return $this->renderer->renderNextPage(null, $context);
            case 'previous_page':
                return $this->renderer->renderPreviousPage(null, $context);
            case 'next_sermon':
                return $this->renderer->renderNextSermon($sermon, $context);
            case 'prev_sermon':
                return $this->renderer->renderPrevSermon($sermon, $context);
            case 'sameday_sermon':
                return $this->renderer->renderSamedaySermon($sermon, $context);

            // Podcast tags
            case 'podcast':
                return $this->renderer->renderPodcast(null, $context);
            case 'podcast_for_search':
                return $this->renderer->renderPodcastForSearch(null, $context);
            case 'itunes_podcast':
                return $this->renderer->renderItunesPodcast(null, $context);
            case 'itunes_podcast_for_search':
                return $this->renderer->renderItunesPodcastForSearch(null, $context);
            case 'podcasticon':
                return $this->renderer->renderPodcastIcon(null, $context);
            case 'podcasticon_for_search':
                return $this->renderer->renderPodcastIconForSearch(null, $context);

            // File/embed tags (used in loops)
            case 'file':
                $mediaName = $currentMedia->name ?? '';
                return $this->renderer->renderFile($mediaName, $context);
            case 'file_with_download':
                $mediaName = $currentMedia->name ?? '';
                return $this->renderer->renderFileWithDownload($mediaName, $context);
            case 'embed':
                $mediaName = $currentMedia->name ?? '';
                return $this->renderer->renderEmbed($mediaName, $context);

            // Passage tag (used in loop)
            case 'passage':
                if ($currentPassage !== null) {
                    $start = $currentPassage['start'] ?? [];
                    $end = $currentPassage['end'] ?? [];
                    return $this->renderer->renderPassage($start, $end);
                }
                return '';

            // Bible translation tags
            case 'biblepassage':
                return $this->renderer->renderBiblePassage($sermon, $context);
            case 'esvtext':
                return $this->renderer->renderEsvText($sermon, $context);
            case 'kjvtext':
                return $this->renderer->renderKjvText($sermon, $context);
            case 'asvtext':
                return $this->renderer->renderAsvText($sermon, $context);
            case 'nettext':
                return $this->renderer->renderNetText($sermon, $context);
            case 'ylttext':
                return $this->renderer->renderYltText($sermon, $context);
            case 'webtext':
                return $this->renderer->renderWebText($sermon, $context);
            case 'akjvtext':
                return $this->renderer->renderAkjvText($sermon, $context);
            case 'hnvtext':
                return $this->renderer->renderHnvText($sermon, $context);
            case 'lbrvtext':
                return $this->renderer->renderLbrvText($sermon, $context);
            case 'cornilescutext':
                return $this->renderer->renderCornilescuText($sermon, $context);
            case 'synodaltext':
                return $this->renderer->renderSynodalText($sermon, $context);

            // Misc tags
            case 'most_popular':
                return $this->renderer->renderMostPopular(null, $context);
            case 'tag_cloud':
                return $this->renderer->renderTagCloud(null, $context);
            case 'creditlink':
                return $this->renderer->renderCreditLink(null, $context);

            default:
                // Unknown tag - preserve as-is
                return '[' . $tagName . ']';
        }
    }

    /**
     * Process loop markers and iterate over data arrays.
     *
     * @param string $content The content with loop markers.
     * @param array<string, mixed> $data The data array.
     * @param string $context The context.
     * @return string The processed content with loops expanded.
     */
    private function processLoops(string $content, array $data, string $context): string
    {
        // Process sermons loop first (outermost)
        $content = $this->processSermonsLoop($content, $data, $context);

        // Process files loop
        $content = $this->processFilesLoop($content, $data, $context);

        // Process embed loop
        $content = $this->processEmbedLoop($content, $data, $context);

        // Process passages loop
        $content = $this->processPassagesLoop($content, $data, $context);

        return $content;
    }

    /**
     * Process the sermons loop.
     *
     * @param string $content The content with sermons loop markers.
     * @param array<string, mixed> $data The data array.
     * @param string $context The context.
     * @return string The content with the sermons loop expanded.
     */
    private function processSermonsLoop(string $content, array $data, string $context): string
    {
        $startMarker = self::SERMONS_LOOP_START;
        $endMarker = self::SERMONS_LOOP_END;

        $startPos = strpos($content, $startMarker);
        if ($startPos === false) {
            return $content;
        }

        $endPos = strpos($content, $endMarker);
        if ($endPos === false) {
            return $content;
        }

        $before = substr($content, 0, $startPos);
        $loopTemplate = substr($content, $startPos + strlen($startMarker), $endPos - $startPos - strlen($startMarker));
        $after = substr($content, $endPos + strlen($endMarker));

        $sermons = $data['sermons'] ?? [];
        $loopContent = '';

        foreach ($sermons as $sermon) {
            // Create a data array with the current sermon
            $loopData = array_merge($data, ['Sermon' => $sermon]);

            // Process nested loops FIRST (files, embeds) for this sermon
            // This ensures [file], [embed], [passage] tags get proper context
            $iterationContent = $this->processFilesLoop($loopTemplate, $loopData, $context);
            $iterationContent = $this->processEmbedLoop($iterationContent, $loopData, $context);
            $iterationContent = $this->processPassagesLoop($iterationContent, $loopData, $context);

            // Then replace sermon-level tags
            $iterationContent = $this->replaceTags($iterationContent, $loopData, $context, $sermon);

            $loopContent .= $iterationContent;
        }

        return $before . $loopContent . $after;
    }

    /**
     * Process the files loop.
     *
     * @param string $content The content with files loop markers.
     * @param array<string, mixed> $data The data array.
     * @param string $context The context.
     * @return string The content with the files loop expanded.
     */
    private function processFilesLoop(string $content, array $data, string $context): string
    {
        $startMarker = self::FILES_LOOP_START;
        $endMarker = self::FILES_LOOP_END;

        $startPos = strpos($content, $startMarker);
        if ($startPos === false) {
            return $content;
        }

        $endPos = strpos($content, $endMarker);
        if ($endPos === false) {
            return $content;
        }

        $before = substr($content, 0, $startPos);
        $loopTemplate = substr($content, $startPos + strlen($startMarker), $endPos - $startPos - strlen($startMarker));
        $after = substr($content, $endPos + strlen($endMarker));

        // Filter media to exclude Code type (those go in embed loop)
        $allMedia = $data['media'] ?? [];
        $files = array_filter($allMedia, function ($media) {
            return !isset($media->type->type) || $media->type->type !== 'Code';
        });

        $loopContent = '';

        foreach ($files as $media) {
            // Replace tags in the loop template for this file
            $iterationContent = $this->replaceTags($loopTemplate, $data, $context, null, $media);
            $loopContent .= $iterationContent;
        }

        return $before . $loopContent . $after;
    }

    /**
     * Process the embed loop.
     *
     * @param string $content The content with embed loop markers.
     * @param array<string, mixed> $data The data array.
     * @param string $context The context.
     * @return string The content with the embed loop expanded.
     */
    private function processEmbedLoop(string $content, array $data, string $context): string
    {
        $startMarker = self::EMBED_LOOP_START;
        $endMarker = self::EMBED_LOOP_END;

        $startPos = strpos($content, $startMarker);
        if ($startPos === false) {
            return $content;
        }

        $endPos = strpos($content, $endMarker);
        if ($endPos === false) {
            return $content;
        }

        $before = substr($content, 0, $startPos);
        $loopTemplate = substr($content, $startPos + strlen($startMarker), $endPos - $startPos - strlen($startMarker));
        $after = substr($content, $endPos + strlen($endMarker));

        // Filter media to only Code type (embeds)
        $allMedia = $data['media'] ?? [];
        $embeds = array_filter($allMedia, function ($media) {
            return isset($media->type->type) && $media->type->type === 'Code';
        });

        $loopContent = '';

        foreach ($embeds as $media) {
            // Replace tags in the loop template for this embed
            $iterationContent = $this->replaceTags($loopTemplate, $data, $context, null, $media);
            $loopContent .= $iterationContent;
        }

        return $before . $loopContent . $after;
    }

    /**
     * Process the passages loop.
     *
     * @param string $content The content with passages loop markers.
     * @param array<string, mixed> $data The data array.
     * @param string $context The context.
     * @return string The content with the passages loop expanded.
     */
    private function processPassagesLoop(string $content, array $data, string $context): string
    {
        $startMarker = self::PASSAGES_LOOP_START;
        $endMarker = self::PASSAGES_LOOP_END;

        $startPos = strpos($content, $startMarker);
        if ($startPos === false) {
            return $content;
        }

        $endPos = strpos($content, $endMarker);
        if ($endPos === false) {
            return $content;
        }

        $before = substr($content, 0, $startPos);
        $loopTemplate = substr($content, $startPos + strlen($startMarker), $endPos - $startPos - strlen($startMarker));
        $after = substr($content, $endPos + strlen($endMarker));

        $sermon = $data['Sermon'] ?? null;
        $loopContent = '';

        if ($sermon !== null) {
            $starts = is_array($sermon->start) ? $sermon->start : [];
            $ends = is_array($sermon->end) ? $sermon->end : [];

            $count = min(count($starts), count($ends));

            for ($i = 0; $i < $count; $i++) {
                $passageData = [
                    'start' => $starts[$i] ?? [],
                    'end' => $ends[$i] ?? [],
                ];

                // Replace tags in the loop template for this passage
                $iterationContent = $this->replaceTags($loopTemplate, $data, $context, null, null, $passageData);
                $loopContent .= $iterationContent;
            }
        }

        return $before . $loopContent . $after;
    }
}
