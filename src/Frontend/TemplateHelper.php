<?php

/**
 * Template Helper for common template rendering functions.
 *
 * Provides static methods for rendering template elements like tags,
 * dates, preacher information, and sermon navigation links.
 *
 * @package SermonBrowser\Frontend
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Frontend;

use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Facades\Sermon;
use SermonBrowser\Facades\Tag;

/**
 * Class TemplateHelper
 *
 * Static helper methods for template rendering functions.
 */
class TemplateHelper
{
    /**
     * Print tags as comma-separated links.
     *
     * @param array<string> $tags Array of tag names.
     * @return void
     */
    public static function printTags(array $tags): void
    {
        $out = [];
        foreach ($tags as $tag) {
            $tag = stripslashes($tag);
            $out[] = '<a href="' . sb_get_tag_link($tag) . '">' . $tag . '</a>';
        }
        $tagsString = implode(', ', $out);
        echo $tagsString;
    }

    /**
     * Print tag cloud with font size scaling.
     *
     * @param int $minfont Minimum font size percentage (default 80).
     * @param int $maxfont Maximum font size percentage (default 150).
     * @return void
     */
    public static function printTagClouds(int $minfont = 80, int $maxfont = 150): void
    {
        $tags = Tag::findAllWithSermonCount();

        if (empty($tags)) {
            return;
        }

        // Build count array from tag objects
        $cnt = [];
        foreach ($tags as $tag) {
            if (!empty($tag->name)) {
                $cnt[$tag->name] = (int) $tag->sermon_count;
            }
        }

        if (empty($cnt)) {
            return;
        }

        $fontrange = $maxfont - $minfont;
        $maxcnt = max($cnt);
        $mincnt = min($cnt);
        $minlog = log($mincnt);
        $maxlog = log($maxcnt);
        $logrange = $maxlog == $minlog ? 1 : $maxlog - $minlog;
        arsort($cnt);
        $out = [];
        foreach ($cnt as $tag => $count) {
            $size = $minfont + $fontrange * (log($count) - $minlog) / $logrange;
            $out[] = '<a style="font-size:' . (int) $size . '%" href="' . sb_get_tag_link($tag) . '">' . $tag . '</a>';
        }
        echo implode(' ', $out);
    }

    /**
     * Format sermon date for display.
     *
     * @param object $sermon The sermon object with datetime and optional time properties.
     * @return string The formatted date string or "Unknown Date" if invalid.
     */
    public static function formattedDate(object $sermon): string
    {
        if (isset($sermon->time) && $sermon->time !== '') {
            // Sermon time is available - not currently used but kept for compatibility
        } else {
            sb_default_time($sermon->sid);
        }

        if ($sermon->datetime === '1970-01-01 00:00:00') {
            return __('Unknown Date', 'sermon-browser');
        }

        return date_i18n(get_option('date_format'), strtotime($sermon->datetime));
    }

    /**
     * Print edit sermon link if user has permission.
     *
     * @param int $id The sermon ID.
     * @return void
     */
    public static function editLink(int $id): void
    {
        if (current_user_can('publish_posts')) {
            echo '<div class="sb_edit_link"><a href="'
                . admin_url('admin.php?page=sermon-browser/new_sermon.php&mid=' . $id)
                . '">Edit Sermon</a></div>';
        }
    }

    /**
     * Print preacher description if available.
     *
     * @param object $sermon The sermon object with preacher_description property.
     * @return void
     */
    public static function printPreacherDescription(object $sermon): void
    {
        if (strlen($sermon->preacher_description) > 0) {
            echo "<div class='preacher-description'><span class='about'>"
                . __('About', 'sermon-browser') . ' ' . stripslashes($sermon->preacher)
                . ': </span>';
            echo "<span class='description'>" . stripslashes($sermon->preacher_description) . "</span></div>";
        }
    }

    /**
     * Print preacher image if available.
     *
     * @param object $sermon The sermon object with image property.
     * @return void
     */
    public static function printPreacherImage(object $sermon): void
    {
        if ($sermon->image) {
            echo "<img alt='" . stripslashes($sermon->preacher) . "' class='preacher' src='"
                . trailingslashit(site_url()) . OptionsManager::get('upload_dir') . 'images/' . $sermon->image . "'>";
        }
    }

    /**
     * Print link to the next sermon by date.
     *
     * @param object $sermon The current sermon object with datetime and id properties.
     * @return void
     */
    public static function printNextSermonLink(object $sermon): void
    {
        $next = Sermon::findNextByDate($sermon->datetime, (int) $sermon->id);
        if (!$next) {
            return;
        }
        echo '<a href="';
        sb_print_sermon_link($next);
        echo '">' . stripslashes($next->title) . ' &raquo;</a>';
    }

    /**
     * Print link to the previous sermon by date.
     *
     * @param object $sermon The current sermon object with datetime and id properties.
     * @return void
     */
    public static function printPrevSermonLink(object $sermon): void
    {
        $prev = Sermon::findPreviousByDate($sermon->datetime, (int) $sermon->id);
        if (!$prev) {
            return;
        }
        echo '<a href="';
        sb_print_sermon_link($prev);
        echo '">&laquo; ' . stripslashes($prev->title) . '</a>';
    }

    /**
     * Print links to sermons preached on the same day.
     *
     * @param object $sermon The current sermon object with datetime and id properties.
     * @return void
     */
    public static function printSamedaySermonLink(object $sermon): void
    {
        $same = Sermon::findSameDay($sermon->datetime, (int) $sermon->id);
        if (empty($same)) {
            _e('None', 'sermon-browser');
            return;
        }
        $output = [];
        foreach ($same as $cur) {
            $output[] = '<a href="' . sb_print_sermon_link($cur, false) . '">' . stripslashes($cur->title) . '</a>';
        }
        echo implode(', ', $output);
    }
}
