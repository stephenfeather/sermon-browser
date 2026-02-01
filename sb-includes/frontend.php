<?php

use SermonBrowser\Frontend\Widgets\PopularWidget;
use SermonBrowser\Frontend\Widgets\SermonWidget;
use SermonBrowser\Frontend\Widgets\TagCloudWidget;

/**
 * Deprecated function - displays error message
 *
 * @deprecated Use sb_display_sermons() or the sermon browser widget instead
 * @param array $options
 */
function display_sermons($_options = array())
{
    echo "This function is now deprecated. Use sb_display_sermons or the sermon browser widget, instead.";
}

/**
 * Display sermons for template use.
 *
 * @param array $options Display options.
 */
function sb_display_sermons($options = array())
{
    SermonWidget::display((array) $options);
}

/**
 * Display the sermon widget in sidebar.
 *
 * @param array     $args        Widget arguments.
 * @param array|int $widget_args Widget instance arguments.
 */
function sb_widget_sermon($args, $widget_args = 1)
{
    SermonWidget::widget($args, $widget_args);
}

/**
 * Display the tag cloud widget in sidebar.
 *
 * @param array $args Widget arguments.
 */
function sb_widget_tag_cloud($args)
{
    TagCloudWidget::widget($args);
}

function sb_admin_bar_menu()
{
    \SermonBrowser\Admin\AdminBarMenu::register();
}

// Sorts an object by rank
function sb_sort_object($a, $b)
{
    if ($a->rank ==  $b->rank) {
        return 0;
    }
    return ($a->rank < $b->rank) ? -1 : 1;
}

/**
 * Display the most popular sermons widget in sidebar.
 *
 * @param array $args Widget arguments.
 */
function sb_widget_popular($args)
{
    PopularWidget::widget($args);
}

/**
 * Print the most popular widget with default styling.
 *
 * Convenience function for template usage.
 */
function sb_print_most_popular()
{
    PopularWidget::printMostPopular();
}

//Modify page title
function sb_page_title($title)
{
    return \SermonBrowser\Frontend\PageTitle::modify((string) $title);
}

/**
 * Downloads external webpage. Used to add Bible passages to sermon page.
 *
 * @param string $page_url The URL to fetch.
 * @param array|string $headers Optional headers.
 * @return string|null The response body.
 */
function sb_download_page($page_url, $headers = array())
{
    return \SermonBrowser\Frontend\BibleText::downloadPage($page_url, $headers);
}

/**
 * Returns human friendly Bible reference (e.g. John 3:1-16, not John 3:1-John 3:16).
 *
 * @param array $start Start reference with book, chapter, verse keys.
 * @param array $end End reference with book, chapter, verse keys.
 * @param bool $add_link Whether to add filter links to book names.
 * @return string The formatted reference.
 */
function sb_tidy_reference($start, $end, $add_link = false)
{
    return \SermonBrowser\Frontend\BibleText::tidyReference($start, $end, $add_link);
}

/**
 * Print unstyled bible passage.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return void
 */
function sb_print_bible_passage($start, $end)
{
    \SermonBrowser\Frontend\BibleText::printBiblePassage($start, $end);
}

/**
 * Returns human friendly Bible reference with link to filter.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return string The formatted reference with links.
 */
function sb_get_books($start, $end)
{
    return \SermonBrowser\Frontend\BibleText::getBooks($start, $end);
}

/**
 * Add Bible text to single sermon page.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @param string $version Bible version code.
 * @return string The Bible text HTML.
 */
function sb_add_bible_text($start, $end, $version)
{
    return \SermonBrowser\Frontend\BibleText::addBibleText($start, $end, $version);
}

/**
 * Returns ESV text.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return string The ESV text HTML.
 */
function sb_add_esv_text($start, $end)
{
    return \SermonBrowser\Frontend\BibleText::addEsvText($start, $end);
}

/**
 * Converts XML string to object.
 *
 * @param string $content The XML string.
 * @return SimpleXMLElement The parsed XML object.
 */
function sb_get_xml($content)
{
    return \SermonBrowser\Frontend\BibleText::getXml($content);
}

/**
 * Returns NET Bible text.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @return string The NET Bible text HTML.
 */
function sb_add_net_text($start, $end)
{
    return \SermonBrowser\Frontend\BibleText::addNetText($start, $end);
}

/**
 * Returns Bible text using SermonBrowser's own API.
 *
 * @param array $start Start reference.
 * @param array $end End reference.
 * @param string $version Bible version code.
 * @return string The Bible text HTML.
 */
function sb_add_other_bibles($start, $end, $version)
{
    return \SermonBrowser\Frontend\BibleText::addOtherBibles($start, $end, $version);
}

//Adds edit sermon link if current user has edit rights
function sb_edit_link($id)
{
    \SermonBrowser\Frontend\TemplateHelper::editLink((int) $id);
}

// Returns URL for search links
// Relative links now deprecated
function sb_build_url($arr, $clear = false)
{
    return \SermonBrowser\Frontend\UrlBuilder::build($arr, $clear);
}

// Adds javascript and CSS where required
function sb_add_headers()
{
    \SermonBrowser\Frontend\AssetLoader::addHeaders();
}

// Formats date into words
function sb_formatted_date($sermon)
{
    return \SermonBrowser\Frontend\TemplateHelper::formattedDate($sermon);
}

// Returns podcast URL
function sb_podcast_url()
{
    return \SermonBrowser\Frontend\UrlBuilder::podcastUrl();
}

// Prints sermon search URL
function sb_print_sermon_link($sermon, $echo = true)
{
    $url = \SermonBrowser\Frontend\UrlBuilder::sermonLink($sermon);
    if ($echo) {
        echo $url;
    } else {
        return $url;
    }
}

// Prints preacher search URL
function sb_print_preacher_link($sermon)
{
    echo \SermonBrowser\Frontend\UrlBuilder::preacherLink($sermon);
}

// Prints series search URL
function sb_print_series_link($sermon)
{
    echo \SermonBrowser\Frontend\UrlBuilder::seriesLink($sermon);
}

// Prints service search URL
function sb_print_service_link($sermon)
{
    echo \SermonBrowser\Frontend\UrlBuilder::serviceLink($sermon);
}

// Prints bible book search URL
function sb_get_book_link($book_name)
{
    return \SermonBrowser\Frontend\UrlBuilder::bookLink($book_name);
}

// Prints tag search URL
function sb_get_tag_link($tag)
{
    return \SermonBrowser\Frontend\UrlBuilder::tagLink($tag);
}

// Prints tags
function sb_print_tags($tags)
{
    \SermonBrowser\Frontend\TemplateHelper::printTags((array) $tags);
}

//Prints tag cloud
function sb_print_tag_clouds($minfont = 80, $maxfont = 150)
{
    \SermonBrowser\Frontend\TemplateHelper::printTagClouds((int) $minfont, (int) $maxfont);
}

//Prints link to next page
function sb_print_next_page_link($limit = 0)
{
    \SermonBrowser\Frontend\Pagination::printNextPageLink((int) $limit);
}

//Prints link to previous page
function sb_print_prev_page_link($limit = 0)
{
    \SermonBrowser\Frontend\Pagination::printPrevPageLink((int) $limit);
}

// Print link to attached files
function sb_print_url($url)
{
    \SermonBrowser\Frontend\FileDisplay::printUrl($url);
}

// Print link to attached external URLs
function sb_print_url_link($url)
{
    \SermonBrowser\Frontend\FileDisplay::printUrlLink($url);
}

//Decode base64 encoded data
function sb_print_code($code)
{
    \SermonBrowser\Frontend\FileDisplay::printCode($code);
}

//Prints preacher description
function sb_print_preacher_description($sermon)
{
    \SermonBrowser\Frontend\TemplateHelper::printPreacherDescription($sermon);
}

//Prints preacher image
function sb_print_preacher_image($sermon)
{
    \SermonBrowser\Frontend\TemplateHelper::printPreacherImage($sermon);
}

//Prints link to sermon preached next (but not today)
function sb_print_next_sermon_link($sermon)
{
    \SermonBrowser\Frontend\TemplateHelper::printNextSermonLink($sermon);
}

//Prints link to sermon preached on previous days
function sb_print_prev_sermon_link($sermon)
{
    \SermonBrowser\Frontend\TemplateHelper::printPrevSermonLink($sermon);
}

//Prints links to other sermons preached on the same day
function sb_print_sameday_sermon_link($sermon)
{
    \SermonBrowser\Frontend\TemplateHelper::printSamedaySermonLink($sermon);
}

//Gets single sermon from the database
function sb_get_single_sermon($id)
{
    $id = (int) $id;

    // Get sermon with relations using Facade (includes preacher, service, series)
    $sermon = \SermonBrowser\Facades\Sermon::findForTemplate($id);

    if (!$sermon) {
        return false;
    }

    // Handle null series (series_id = 0)
    if ($sermon->ssid === null) {
        $sermon->ssid = 0;
        $sermon->series = '';
    }

    // Get stuff (files and code) using Facade
    $stuff = \SermonBrowser\Facades\File::findBySermon($id);
    $file = [];
    $code = [];
    foreach ($stuff as $cur) {
        if ($cur->type === 'file') {
            $file[] = $cur->name;
        } elseif ($cur->type === 'code') {
            $code[] = $cur->name;
        }
    }

    // Get tags using Facade
    $tagObjects = \SermonBrowser\Facades\Tag::findBySermon($id);
    $tags = [];
    foreach ($tagObjects as $tag) {
        $tags[] = $tag->name;
    }

    // Unserialize start/end passages
    $sermon->start = unserialize($sermon->start);
    $sermon->end = unserialize($sermon->end);

    return [
        'Sermon' => $sermon,
        'Files' => $file,
        'Code' => $code,
        'Tags' => $tags,
    ];
}

//Prints the filter line for a given parameter
function sb_print_filter_line($id, $results, $filter, $display, $max_num = 7)
{
    \SermonBrowser\Frontend\FilterRenderer::renderLine($id, $results, $filter, $display, $max_num);
}

//Prints the filter line for the date parameter
function sb_print_date_filter_line($dates)
{
    \SermonBrowser\Frontend\FilterRenderer::renderDateLine($dates);
}

//Returns the filter URL minus a given parameter
function sb_url_minus_parameter($param1, $param2 = '')
{
    return \SermonBrowser\Frontend\FilterRenderer::urlMinusParameter($param1, $param2);
}

//Displays the filter on sermon search page
function sb_print_filters($filter)
{
    \SermonBrowser\Frontend\FilterRenderer::render($filter);
}
// Returns the first MP3 file attached to a sermon
// Stats have to be turned off for iTunes compatibility
function sb_first_mp3($sermon, $stats = true)
{
    return \SermonBrowser\Frontend\FileDisplay::firstMp3($sermon, $stats);
}
