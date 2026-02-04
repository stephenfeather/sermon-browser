# Podcast Feed

Sermon Browser generates RSS 2.0 podcast feeds with iTunes extensions for distributing sermons via podcast directories like Apple Podcasts, Spotify, and Google Podcasts. The feeds include automatic enclosure metadata, support custom filtering, and are fully compatible with podcast standards.

## Overview

The podcast feed is automatically generated and accessible via URL parameters on the main sermons page.

```php { .api }
/**
 * Base podcast feed URL
 *
 * Format: {sermons_page_url}?podcast
 * Content-Type: application/rss+xml
 * Encoding: UTF-8
 */

// Example URLs:
// https://example.com/sermons/?podcast
// https://example.com/?page_id=123&podcast
```

## Capabilities

### Basic Podcast Feed

Generate a podcast feed containing all sermons.

```php { .api }
/**
 * All sermons podcast feed
 *
 * URL: ?podcast
 *
 * Returns RSS 2.0 feed with all sermons ordered by date (newest first)
 * Includes enclosures for all MP3 files attached to sermons
 */

// Example
$feed_url = sb_podcast_url();
// Returns: https://example.com/sermons/?podcast
```

**Usage:**

```php
// Get podcast feed URL
$url = sb_podcast_url();
echo '<a href="' . esc_url($url) . '">Subscribe to Podcast</a>';

// Or use template tag
<link rel="alternate" type="application/rss+xml"
      title="Sermon Podcast"
      href="<?php echo sb_podcast_url(); ?>">
```

### Filtered Podcast Feeds

Generate podcast feeds filtered by preacher, series, or service.

```php { .api }
/**
 * Preacher-specific podcast feed
 *
 * URL: ?podcast&preacher={id}
 *
 * Returns RSS feed containing only sermons by specified preacher
 */

/**
 * Series-specific podcast feed
 *
 * URL: ?podcast&series={id}
 *
 * Returns RSS feed containing only sermons in specified series
 */

/**
 * Service-specific podcast feed
 *
 * URL: ?podcast&service={id}
 *
 * Returns RSS feed containing only sermons from specified service
 */
```

**Usage:**

```php
// Preacher podcast feed
$preacher_id = 5;
$url = sb_podcast_url() . '&preacher=' . $preacher_id;
echo '<a href="' . esc_url($url) . '">Subscribe to John Smith\'s sermons</a>';

// Series podcast feed
$series_id = 12;
$url = sb_podcast_url() . '&series=' . $series_id;
echo '<a href="' . esc_url($url) . '">Subscribe to Gospel of John series</a>';

// Service podcast feed
$service_id = 1;
$url = sb_podcast_url() . '&service=' . $service_id;
echo '<a href="' . esc_url($url) . '">Subscribe to Sunday Morning sermons</a>';
```

### Feed Metadata

Each podcast feed includes channel-level metadata:

```php { .api }
/**
 * Channel metadata (RSS 2.0)
 *
 * - title: Site name + " Sermons" (or filtered version)
 * - link: Site URL
 * - description: Site description or custom podcast description
 * - language: Site language (e.g., en-US)
 * - copyright: Site name
 * - lastBuildDate: RFC 2822 formatted date
 * - generator: "Sermon Browser"
 *
 * iTunes extensions:
 * - itunes:author: Site name
 * - itunes:subtitle: Short description
 * - itunes:summary: Full description
 * - itunes:owner: Site email and name
 * - itunes:image: Site icon or custom podcast image
 * - itunes:category: Religion & Spirituality > Christianity
 * - itunes:explicit: no
 */
```

### Item (Sermon) Data

Each sermon in the feed includes:

```php { .api }
/**
 * Sermon item data (RSS 2.0)
 *
 * - title: Sermon title
 * - link: Permalink to sermon page
 * - description: Sermon description (HTML)
 * - pubDate: RFC 2822 formatted sermon date
 * - guid: Unique identifier (permalink)
 *
 * Enclosure:
 * - url: Direct link to MP3 file
 * - length: File size in bytes
 * - type: MIME type (audio/mpeg)
 *
 * iTunes extensions:
 * - itunes:author: Preacher name
 * - itunes:subtitle: Bible passage
 * - itunes:summary: Description
 * - itunes:duration: HH:MM:SS format
 * - itunes:keywords: Tags as comma-separated list
 */
```

## Podcast Functions

Template tag functions for podcast feed generation:

```php { .api }
/**
 * Get podcast feed URL
 *
 * @return string Full URL to podcast feed
 */
function sb_podcast_url(): string;

/**
 * Print ISO 8601 formatted date for podcast
 *
 * @param object $sermon Sermon object
 * @return void Outputs RFC 2822 date string
 */
function sb_print_iso_date($sermon): void;

/**
 * Get media file size for enclosure
 *
 * @param string $media_name File path or URL
 * @param string $media_type Media type ('file', 'url', 'code')
 * @return int File size in bytes (0 if unable to determine)
 */
function sb_media_size($media_name, $media_type): int;

/**
 * Get MP3 duration
 *
 * @param string $media_name File path
 * @param string $media_type Media type ('file', 'url', 'code')
 * @return string Duration in HH:MM:SS format
 */
function sb_mp3_duration($media_name, $media_type): string;

/**
 * XML entity encode string for RSS
 *
 * @param string $string String to encode
 * @return string Encoded string safe for XML
 */
function sb_xml_entity_encode($string): string;

/**
 * Get podcast file URL
 *
 * @param string $media_name File path
 * @param string $media_type Media type ('file', 'url', 'code')
 * @return string Full URL to file for enclosure
 */
function sb_podcast_file_url($media_name, $media_type): string;

/**
 * Get MIME type for file
 *
 * @param string $media_name File path or URL
 * @return string MIME type (e.g., 'audio/mpeg', 'video/mp4')
 */
function sb_mime_type($media_name): string;
```

## Feed Generation Example

```php
// Generate custom podcast feed
header('Content-Type: application/rss+xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0"
     xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
     xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?php echo get_bloginfo('name'); ?> Sermons</title>
    <link><?php echo home_url(); ?></link>
    <description><?php echo get_bloginfo('description'); ?></description>
    <language>en-US</language>
    <lastBuildDate><?php echo date(DATE_RFC2822); ?></lastBuildDate>

    <itunes:author><?php echo get_bloginfo('name'); ?></itunes:author>
    <itunes:image href="<?php echo get_site_icon_url(); ?>"/>
    <itunes:category text="Religion &amp; Spirituality">
        <itunes:category text="Christianity"/>
    </itunes:category>

    <?php
    // Get sermons
    $sermons = sb_get_sermons([], 'DESC', 1, 100, true);

    foreach ($sermons as $sermon):
        // Get first MP3 file
        $files = sb_get_stuff($sermon, true);
        if (empty($files)) continue;
        $file = $files[0];
        ?>
        <item>
            <title><?php echo sb_xml_entity_encode($sermon->title); ?></title>
            <link><?php sb_print_sermon_link($sermon, true); ?></link>
            <description><![CDATA[<?php echo $sermon->description; ?>]]></description>
            <pubDate><?php sb_print_iso_date($sermon); ?></pubDate>
            <guid><?php sb_print_sermon_link($sermon, true); ?></guid>

            <enclosure
                url="<?php echo sb_podcast_file_url($file->stuff, $file->stuff_type); ?>"
                length="<?php echo sb_media_size($file->stuff, $file->stuff_type); ?>"
                type="<?php echo sb_mime_type($file->stuff); ?>"/>

            <itunes:author><?php echo sb_xml_entity_encode($sermon->preacher_name); ?></itunes:author>
            <itunes:subtitle><?php echo sb_xml_entity_encode($sermon->bible_passage); ?></itunes:subtitle>
            <itunes:duration><?php echo sb_mp3_duration($file->stuff, $file->stuff_type); ?></itunes:duration>
        </item>
    <?php endforeach; ?>
</channel>
</rss>
```

## Podcast Optimization

### File Size Caching

File sizes are calculated on-demand but should be cached:

```php
// Cache file size in transient
$file_size = get_transient('sb_file_size_' . md5($file_path));
if ($file_size === false) {
    $file_size = sb_media_size($file_path, 'file');
    set_transient('sb_file_size_' . md5($file_path), $file_size, DAY_IN_SECONDS);
}
```

### Duration Parsing

MP3 duration is parsed from ID3 tags:

```php
// Get duration from MP3 file
$duration = sb_mp3_duration('/path/to/sermon.mp3', 'file');
// Returns: "01:23:45" (HH:MM:SS)

// Duration is calculated using getID3 library or ffprobe if available
```

### Feed Caching

Podcast feeds should be cached to reduce server load:

```php
// Cache feed for 1 hour
$cache_key = 'sb_podcast_' . md5($_SERVER['QUERY_STRING']);
$feed_xml = get_transient($cache_key);

if ($feed_xml === false) {
    // Generate feed
    ob_start();
    // ... feed generation code ...
    $feed_xml = ob_get_clean();

    // Cache for 1 hour
    set_transient($cache_key, $feed_xml, HOUR_IN_SECONDS);
}

echo $feed_xml;
```

## Podcast Directories

### Apple Podcasts

Submit podcast feed to Apple Podcasts:

1. Get feed URL: `https://example.com/sermons/?podcast`
2. Open Podcasts Connect: https://podcastsconnect.apple.com
3. Submit feed URL
4. Verify ownership
5. Wait for approval

### Spotify

Submit to Spotify for Podcasters:

1. Sign up at https://podcasters.spotify.com
2. Add podcast feed URL
3. Verify ownership
4. Complete metadata

### Google Podcasts

Submit to Google Podcasts Manager:

1. Visit https://podcastsmanager.google.com
2. Add feed URL
3. Verify ownership
4. Publish

## iTunes Tags

The feed includes all required iTunes podcast tags:

```xml
<itunes:author>Church Name</itunes:author>
<itunes:subtitle>Weekly sermons from Church Name</itunes:subtitle>
<itunes:summary>Full description of podcast content...</itunes:summary>
<itunes:owner>
    <itunes:name>Pastor Name</itunes:name>
    <itunes:email>podcast@example.com</itunes:email>
</itunes:owner>
<itunes:image href="https://example.com/podcast-artwork.jpg"/>
<itunes:category text="Religion &amp; Spirituality">
    <itunes:category text="Christianity"/>
</itunes:category>
<itunes:explicit>no</itunes:explicit>
```

## Feed Validation

Validate podcast feed before submission:

- **Cast Feed Validator**: https://castfeedvalidator.com
- **Podbase**: https://podba.se/validate
- **iTunes Podcast Spec**: Check against official spec

Common validation errors:
- Missing or invalid enclosure URLs
- Incorrect MIME types
- Invalid XML characters
- Missing iTunes tags
- Incorrect date formats

## Custom Podcast Settings

Customize podcast metadata via WordPress options:

```php
// Set custom podcast title
update_option('sb_podcast_title', 'My Church Sermons');

// Set custom podcast description
update_option('sb_podcast_description', 'Weekly messages from My Church');

// Set custom podcast image
update_option('sb_podcast_image', 'https://example.com/podcast-art.jpg');

// Set custom podcast author
update_option('sb_podcast_author', 'Pastor John Smith');

// Set podcast owner email
update_option('sb_podcast_email', 'podcast@example.com');

// Set podcast category
update_option('sb_podcast_category', 'Religion & Spirituality');
```

## Troubleshooting

### Feed Not Validating

- Check XML validity with validator
- Ensure all enclosure URLs are accessible
- Verify MIME types are correct
- Check for invalid characters in titles/descriptions

### Files Not Playing

- Ensure MP3 files are publicly accessible
- Check file permissions (should be readable)
- Verify correct MIME type (audio/mpeg)
- Test file URLs directly in browser

### Feed Not Updating

- Clear WordPress transient cache
- Clear podcast directory cache (may take 24-48 hours)
- Verify lastBuildDate is current
- Check that new sermons have audio files

### Missing Duration

- Ensure getID3 library is available
- Check MP3 file has valid ID3 tags
- Verify file is readable by PHP
- Fallback: manually set duration in database
