<?php

/**
 * Podcast Feed generator for Sermon Browser.
 *
 * Generates RSS 2.0 podcast feeds with iTunes extensions.
 *
 * @package SermonBrowser\Podcast
 * @since 1.0.0
 */

declare(strict_types=1);

namespace SermonBrowser\Podcast;

/**
 * Class PodcastFeed
 *
 * Renders RSS podcast feed for sermon media files.
 */
final class PodcastFeed
{
    /**
     * Accepted file extensions for podcast enclosures.
     *
     * @var array<string>
     */
    private const ACCEPTED_EXTENSIONS = ['mp3', 'm4a', 'mp4', 'm4v', 'mov', 'wma', 'wmv'];

    /**
     * Maximum number of items in the podcast feed.
     *
     * @var int
     */
    private const MAX_ITEMS = 15;

    /**
     * Render the podcast RSS feed and exit.
     *
     * Fetches sermons, sets headers, and outputs complete RSS XML.
     *
     * @return void Outputs XML and terminates execution.
     */
    public static function render(): void
    {
        $sermons = sb_get_sermons(
            [
                'title'    => isset($_REQUEST['title']) ? esc_sql($_REQUEST['title']) : '',
                'preacher' => isset($_REQUEST['preacher']) ? (int) $_REQUEST['preacher'] : '',
                'date'     => isset($_REQUEST['date']) ? esc_sql($_REQUEST['date']) : '',
                'enddate'  => isset($_REQUEST['enddate']) ? esc_sql($_REQUEST['enddate']) : '',
                'series'   => isset($_REQUEST['series']) ? (int) $_REQUEST['series'] : '',
                'service'  => isset($_REQUEST['service']) ? (int) $_REQUEST['service'] : '',
                'book'     => isset($_REQUEST['book']) ? esc_sql($_REQUEST['book']) : '',
                'tag'      => isset($_REQUEST['stag']) ? esc_sql($_REQUEST['stag']) : '',
            ],
            [
                'by'  => 'm.datetime',
                'dir' => 'desc',
            ],
            1,
            1000000
        );

        wp_timezone_override_offset();

        header('Content-Type: application/rss+xml');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        self::renderChannel($sermons);

        die();
    }

    /**
     * Render the RSS channel element.
     *
     * @param array<object> $sermons Array of sermon objects.
     *
     * @return void Outputs XML.
     */
    private static function renderChannel(array $sermons): void
    {
        $podcastUrl = PodcastHelper::xmlEncode(sb_get_option('podcast_url'));
        $siteName = PodcastHelper::xmlEncode(get_bloginfo('name'));
        $siteDescription = PodcastHelper::xmlEncode(get_bloginfo('description'));
        $siteUrl = PodcastHelper::xmlEncode(site_url());
        $lastBuildDate = PodcastHelper::formatIsoDate($sermons[0] ?? time());

        echo '<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0" ';
        echo 'xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo '<channel>' . "\n";
        echo '    <atom:link href="' . $podcastUrl . '" rel="self" type="application/rss+xml" />' . "\n";
        echo '    <title>' . $siteName . ' Podcast</title>' . "\n";
        echo '    <itunes:author></itunes:author>' . "\n";
        echo '    <description>' . $siteDescription . '</description>' . "\n";
        echo '    <link>' . $siteUrl . '</link>' . "\n";
        echo '    <language>en-us</language>' . "\n";
        echo '    <copyright></copyright>' . "\n";
        echo '    <itunes:explicit>no</itunes:explicit>' . "\n";
        echo '    <itunes:owner>' . "\n";
        echo '        <itunes:name></itunes:name>' . "\n";
        echo '        <itunes:email></itunes:email>' . "\n";
        echo '    </itunes:owner>' . "\n";
        echo "\n";
        echo '    <lastBuildDate>' . $lastBuildDate . '</lastBuildDate>' . "\n";
        echo '    <pubDate>' . $lastBuildDate . '</pubDate>' . "\n";
        echo '    <generator>Wordpress Sermon Browser plugin ' . SB_CURRENT_VERSION;
        echo ' (http://www.sermonbrowser.com/)</generator>' . "\n";
        echo '    <docs>http://blogs.law.harvard.edu/tech/rss</docs>' . "\n";
        echo '    <category>Religion &amp; Spirituality</category>' . "\n";
        echo '    <itunes:category text="Religion &amp; Spirituality"></itunes:category>' . "\n";

        self::renderItems($sermons);

        echo '</channel>' . "\n";
        echo '</rss>' . "\n";
    }

    /**
     * Render RSS item elements for sermons.
     *
     * @param array<object> $sermons Array of sermon objects.
     *
     * @return void Outputs XML.
     */
    private static function renderItems(array $sermons): void
    {
        $itemCount = 0;

        foreach ($sermons as $sermon) {
            if ($itemCount >= self::MAX_ITEMS) {
                break;
            }

            $media = sb_get_stuff($sermon);

            if (!is_array($media['Files'] ?? null) && !is_array($media['URLs'] ?? null)) {
                continue;
            }

            foreach ($media as $mediaType => $mediaNames) {
                if (!is_array($mediaNames) || $mediaType === 'Code') {
                    continue;
                }

                foreach ((array) $mediaNames as $mediaName) {
                    $extension = strtolower(substr($mediaName, -3));

                    if (!in_array($extension, self::ACCEPTED_EXTENSIONS, true)) {
                        continue;
                    }

                    $itemCount++;
                    self::renderItem($sermon, $mediaName, $mediaType);
                }
            }
        }
    }

    /**
     * Render a single RSS item element.
     *
     * @param object $sermon    The sermon object.
     * @param string $mediaName The media filename or URL.
     * @param string $mediaType The type: 'Files' or 'URLs'.
     *
     * @return void Outputs XML.
     */
    private static function renderItem(object $sermon, string $mediaName, string $mediaType): void
    {
        $fileUrl = PodcastHelper::getFileUrl($mediaName, $mediaType);
        $title = PodcastHelper::xmlEncode(stripslashes($sermon->title));
        $link = sb_display_url() . sb_query_char() . 'sermon_id=' . $sermon->id;
        $author = PodcastHelper::xmlEncode(stripslashes($sermon->preacher));
        $service = PodcastHelper::xmlEncode(stripslashes($sermon->service));
        $pubDate = PodcastHelper::formatIsoDate($sermon);
        $mediaSize = PodcastHelper::getMediaSize($mediaName, $mediaType);
        $mimeType = PodcastHelper::getMimeType($mediaName);

        echo '<item>' . "\n";
        echo '        <guid>' . $fileUrl . '</guid>' . "\n";
        echo '        <title>' . $title . '</title>' . "\n";
        echo '        <link>' . $link . '</link>' . "\n";
        echo '        <itunes:author>' . $author . '</itunes:author>' . "\n";

        if ($sermon->description) {
            $description = stripslashes($sermon->description);
            echo '        <description><![CDATA[' . $description . ']]></description>' . "\n";
            echo '        <itunes:summary><![CDATA[' . $description . ']]></itunes:summary>' . "\n";
        }

        echo '        <enclosure url="' . $fileUrl . '" ' . $mediaSize . $mimeType . ' />' . "\n";

        $duration = PodcastHelper::getMp3Duration($mediaName, $mediaType);
        if ($duration) {
            echo '        <itunes:duration>' . $duration . '</itunes:duration>' . "\n";
        }

        echo '        <category>' . $service . '</category>' . "\n";
        echo '        <pubDate>' . $pubDate . '</pubDate>' . "\n";
        echo '    </item>' . "\n";
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
