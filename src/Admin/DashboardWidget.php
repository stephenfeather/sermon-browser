<?php

/**
 * Dashboard Widget Handler.
 *
 * Handles dashboard widget content for SermonBrowser.
 *
 * @package SermonBrowser\Admin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Admin;

use SermonBrowser\Facades\File;
use SermonBrowser\Facades\Preacher;
use SermonBrowser\Facades\Sermon;
use SermonBrowser\Facades\Series;
use SermonBrowser\Facades\Tag;
use SermonBrowser\Frontend\PageResolver;
use SermonBrowser\Repositories\PopularityService;

/**
 * Class DashboardWidget
 *
 * Manages dashboard widget content for SermonBrowser.
 */
final class DashboardWidget
{
    /**
     * Render the "Right Now" dashboard widget content.
     *
     * Displays sermon statistics including file counts, sermon counts,
     * preacher counts, series counts, tags, and download statistics.
     *
     * @return void
     */
    public static function renderRightNow(): void
    {
        $fileCount = File::countByType('file');

        if ($fileCount <= 0) {
            return;
        }

        $stats = self::getStatistics();
        $output = self::buildStatisticsOutput($stats, $fileCount);

        echo $output;
    }

    /**
     * Get dashboard glance items with sermon count.
     *
     * Adds sermon count to the "At a Glance" dashboard widget.
     *
     * @param array<int, string> $items Existing glance items.
     *
     * @return array<int, string> Modified glance items with sermon count.
     */
    public static function glanceItems(array $items): array
    {
        $sermonCount = Sermon::count();

        if ($sermonCount > 0) {
            $text = sprintf(
                _n('%s Sermon', '%s Sermons', $sermonCount, 'sermon-browser'),
                number_format_i18n($sermonCount)
            );
            $items[] = sprintf(
                '<a class="sermon-count" href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=sermon-browser/sermon.php')),
                esc_html($text)
            );
        }

        return $items;
    }

    /**
     * Get sermon statistics.
     *
     * @return array<string, mixed>
     */
    private static function getStatistics(): array
    {
        return [
            'sermonCount'     => Sermon::count(),
            'preacherCount'   => Preacher::count(),
            'seriesCount'     => Series::count(),
            'tagCount'        => Tag::countNonEmpty(),
            'downloadCount'   => File::getTotalDownloads(),
            'mostPopular'     => (new PopularityService())->getMostPopularSermon(),
        ];
    }

    /**
     * Build statistics output HTML.
     *
     * @param array<string, mixed> $stats     Statistics data.
     * @param int                  $fileCount Number of files.
     *
     * @return string HTML output.
     */
    private static function buildStatisticsOutput(array $stats, int $fileCount): string
    {
        $output = '<p class="youhave">' . __("You have") . " ";
        $output .= '<a href="' . admin_url('admin.php?page=sermon-browser/files.php') . '">';
        $output .= sprintf(_n('%s file', '%s files', $fileCount), number_format($fileCount)) . "</a> ";

        if ($stats['sermonCount'] > 0) {
            $output .= __("in") . " " . '<a href="' . admin_url('admin.php?page=sermon-browser/sermon.php') . '">';
            $output .= sprintf(
                _n('%s sermon', '%s sermons', $stats['sermonCount']),
                number_format($stats['sermonCount'])
            ) . "</a> ";
        }

        if ($stats['preacherCount'] > 0) {
            $output .= __("from") . " " . '<a href="' . admin_url('admin.php?page=sermon-browser/preachers.php') . '">';
            $output .= sprintf(
                _n('%s preacher', '%s preachers', $stats['preacherCount']),
                number_format($stats['preacherCount'])
            ) . "</a> ";
        }

        if ($stats['seriesCount'] > 0) {
            $output .= __("in") . " " . '<a href="' . admin_url('admin.php?page=sermon-browser/manage.php') . '">';
            $output .= sprintf(__('%s series'), number_format($stats['seriesCount'])) . "</a> ";
        }

        if ($stats['tagCount'] > 0) {
            $output .= __("using") . " " . sprintf(
                _n('%s tag', '%s tags', $stats['tagCount']),
                number_format($stats['tagCount'])
            ) . " ";
        }

        // Remove trailing space.
        if (substr($output, -1) === " ") {
            $output = substr($output, 0, -1);
        }

        $output .= self::buildDownloadStatistics($stats);
        $output .= '.</p>';

        return $output;
    }

    /**
     * Build download statistics HTML.
     *
     * @param array<string, mixed> $stats Statistics data.
     *
     * @return string HTML output for download statistics.
     */
    private static function buildDownloadStatistics(array $stats): string
    {
        $output = '';
        $downloadCount = $stats['downloadCount'];
        $sermonCount = $stats['sermonCount'];

        if ($downloadCount > 0) {
            $output .= ". " . sprintf(
                _n(
                    'Only one file has been downloaded',
                    'They have been downloaded a total of %s times',
                    $downloadCount
                ),
                number_format($downloadCount)
            );
        }

        if ($downloadCount > 1 && $sermonCount > 0) {
            $downloadAverage = round($downloadCount / $sermonCount, 1);

            $output .= ", " . sprintf(
                _n(
                    'an average of once per sermon',
                    'an average of %d times per sermon',
                    $downloadAverage
                ),
                $downloadAverage
            );

            $mostPopular = $stats['mostPopular'];
            if ($mostPopular) {
                $sermonUrl = PageResolver::getDisplayUrl() . PageResolver::getQueryChar(true)
                    . 'sermon_id=' . $mostPopular->sermon_id;
                $mostPopularTitle = '<a href="' . $sermonUrl . '">' . stripslashes($mostPopular->title) . '</a>';
                $output .= ". " . sprintf(
                    __('The most popular sermon is %s, which has been downloaded %s times'),
                    $mostPopularTitle,
                    number_format($mostPopular->c)
                );
            }
        }

        return $output;
    }

    // =========================================================================
    // Prevent instantiation
    // =========================================================================

    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
        // Static class - cannot be instantiated
    }
}
