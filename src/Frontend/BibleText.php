<?php

/**
 * Bible Text rendering utilities.
 *
 * Provides methods for fetching and rendering Bible text from various
 * translation APIs (ESV, NET, and other versions via SermonBrowser API).
 *
 * @package SermonBrowser\Frontend
 * @since 0.6.0
 */

declare(strict_types=1);

namespace SermonBrowser\Frontend;

use SermonBrowser\Config\Defaults;
use SermonBrowser\Config\OptionsManager;
use SimpleXMLElement;

/**
 * Class BibleText
 *
 * Handles Bible text rendering including reference formatting and
 * fetching from external APIs.
 */
class BibleText
{
    /**
     * Download a page from a URL using WordPress HTTP API.
     *
     * @param string $pageUrl The URL to fetch.
     * @param array<string, string>|string $headers Optional headers to send.
     * @return string|null The response body or null on failure.
     */
    public static function downloadPage(string $pageUrl, array|string $headers = []): ?string
    {
        $headerArray = is_string($headers) ? ['Authorization' => $headers] : $headers;
        $response = wp_remote_get($pageUrl, ['headers' => $headerArray]);

        if (is_array($response) && isset($response['body'])) {
            return $response['body'];
        }

        return null;
    }

    /**
     * Returns a human-friendly Bible reference.
     *
     * Formats a Bible reference to be human-readable, e.g., "John 3:1-16"
     * instead of "John 3:1-John 3:16".
     *
     * @param array<string, mixed> $start Start reference with book, chapter, verse keys.
     * @param array<string, mixed> $end End reference with book, chapter, verse keys.
     * @param bool $addLink Whether to add filter links to book names.
     * @return string The formatted reference.
     */
    public static function tidyReference(array $start, array $end, bool $addLink = false): string
    {
        $startBook = isset($start['book']) ? trim((string) $start['book']) : '';

        if (empty($startBook)) {
            return '';
        }

        $translatedBooks = array_combine(
            Defaults::get('eng_bible_books'),
            Defaults::get('bible_books')
        );

        $startBookTranslated = $translatedBooks[$startBook] ?? $startBook;
        $endBook = isset($end['book']) ? trim((string) $end['book']) : '';
        $endBookTranslated = isset($translatedBooks[$endBook]) ? $translatedBooks[$endBook] : $endBook;

        $startChapter = isset($start['chapter']) ? trim((string) $start['chapter']) : '';
        $endChapter = isset($end['chapter']) ? trim((string) $end['chapter']) : '';
        $startVerse = isset($start['verse']) ? trim((string) $start['verse']) : '';
        $endVerse = isset($end['verse']) ? trim((string) $end['verse']) : '';

        if ($addLink) {
            $startBookTranslated = '<a href="' . esc_url(UrlBuilder::bookLink($startBook)) . '">'
                . esc_html($startBookTranslated) . '</a>';
            $endBookTranslated = '<a href="' . esc_url(UrlBuilder::bookLink($endBook)) . '">'
                . esc_html($endBookTranslated) . '</a>';
        }

        // Build reference based on book/chapter/verse relationships
        $reference = "{$startBookTranslated} {$startChapter}:{$startVerse}";

        if ($startBookTranslated !== $endBookTranslated) {
            $reference .= " - {$endBookTranslated} {$endChapter}:{$endVerse}";
        } elseif ($startChapter !== $endChapter) {
            $reference .= "-{$endChapter}:{$endVerse}";
        } elseif ($startVerse !== $endVerse) {
            $reference .= "-{$endVerse}";
        }

        return $reference;
    }

    /**
     * Print an unstyled Bible passage reference.
     *
     * @param array<string, mixed> $start Start reference.
     * @param array<string, mixed> $end End reference.
     * @return void
     */
    public static function printBiblePassage(array $start, array $end): void
    {
        echo "<p class='bible-passage'>" . self::tidyReference($start, $end) . "</p>";
    }

    /**
     * Returns a Bible reference with linked book names.
     *
     * @param array<string, mixed> $start Start reference.
     * @param array<string, mixed> $end End reference.
     * @return string The formatted reference with links.
     */
    public static function getBooks(array $start, array $end): string
    {
        return self::tidyReference($start, $end, true);
    }

    /**
     * Add Bible text from the appropriate API based on version.
     *
     * @param array<string, mixed> $start Start reference.
     * @param array<string, mixed> $end End reference.
     * @param string $version The Bible version code (esv, net, kjv, etc.).
     * @return string The Bible text HTML.
     */
    public static function addBibleText(array $start, array $end, string $version): string
    {
        if ($version === 'esv') {
            return self::addEsvText($start, $end);
        }

        if ($version === 'net') {
            return self::addNetText($start, $end);
        }

        return self::addOtherBibles($start, $end, $version);
    }

    /**
     * Returns ESV Bible text from the ESV API.
     *
     * @param array<string, mixed> $start Start reference.
     * @param array<string, mixed> $end End reference.
     * @return string The ESV text HTML or fallback to KJV.
     */
    public static function addEsvText(array $start, array $end): string
    {
        $apiKey = OptionsManager::get('esv_api_key');

        if ($apiKey) {
            $header = 'Token ' . $apiKey;
            $reference = rawurlencode(self::tidyReference($start, $end));
            $esvUrl = 'https://api.esv.org/v3/passage/html?q=' . $reference
                . '&include-headings=false&include-footnotes=false';

            $apiBody = self::downloadPage($esvUrl, $header);

            if ($apiBody !== null) {
                $decode = json_decode($apiBody);
                if (is_object($decode) && isset($decode->passages) && is_array($decode->passages)) {
                    return implode('', $decode->passages);
                }
            }
        }

        // Fallback to KJV if no API key or API fails
        return self::addBibleText($start, $end, 'kjv');
    }

    /**
     * Convert an XML string to a SimpleXMLElement object.
     *
     * @param string $content The XML string.
     * @return SimpleXMLElement The parsed XML object.
     */
    public static function getXml(string $content): SimpleXMLElement
    {
        return new SimpleXMLElement($content);
    }

    /**
     * Returns NET Bible text from the Bible.org API.
     *
     * @param array<string, mixed> $start Start reference.
     * @param array<string, mixed> $end End reference.
     * @return string The NET Bible text HTML.
     */
    public static function addNetText(array $start, array $end): string
    {
        $reference = str_replace(' ', '+', self::tidyReference($start, $end));
        $oldChapter = isset($start['chapter']) ? (string) $start['chapter'] : '';
        $netUrl = "http://labs.bible.org/api/xml/verse.php?passage={$reference}";

        $pageContent = self::downloadPage($netUrl . '&formatting=para');
        if ($pageContent === null) {
            return '';
        }

        $xml = self::getXml($pageContent);
        $output = self::processNetXmlItems($xml, $oldChapter);

        $tidyRef = self::tidyReference($start, $end);
        return "<div class=\"net\">\r<h2>{$tidyRef}</h2><p>{$output} "
            . "(<a href=\"http://net.bible.org/?{$reference}\">NET Bible</a>)</p></div>";
    }

    /**
     * Process NET Bible XML items into output string.
     *
     * @param \SimpleXMLElement|null $xml The XML object.
     * @param string $oldChapter Starting chapter for comparison.
     * @return string The processed output.
     */
    private static function processNetXmlItems($xml, string $oldChapter): string
    {
        $output = '';
        if (!isset($xml->item)) {
            return $output;
        }

        foreach ($xml->item as $item) {
            $text = (string) $item->text;
            if ($text === '[[EMPTY]]') {
                continue;
            }

            $text = self::extractParagraphTag($text, $output);
            $output .= self::formatVerseReference($item, $oldChapter);
            $output .= $text;
        }

        return $output;
    }

    /**
     * Extract and prepend paragraph tag if present.
     *
     * @param string $text The text to process.
     * @param string &$output Output string to append paragraph to.
     * @return string The remaining text after paragraph extraction.
     */
    private static function extractParagraphTag(string $text, string &$output): string
    {
        if (substr($text, 0, 8) !== '<p class') {
            return $text;
        }

        $paraend = stripos($text, '>', 8);
        if ($paraend === false) {
            return $text;
        }

        $paraend++;
        $output .= "\n" . substr($text, 0, $paraend);
        return substr($text, $paraend);
    }

    /**
     * Format the verse reference span.
     *
     * @param \SimpleXMLElement $item The XML item.
     * @param string &$oldChapter Chapter tracker (by reference).
     * @return string The formatted verse/chapter span.
     */
    private static function formatVerseReference($item, string &$oldChapter): string
    {
        $itemChapter = (string) $item->chapter;
        $itemVerse = (string) $item->verse;

        if ($oldChapter === $itemChapter) {
            return " <span class=\"verse-num\">{$itemVerse}</span>";
        }

        $oldChapter = $itemChapter;
        return " <span class=\"chapter-num\">{$itemChapter}:{$itemVerse}</span> ";
    }

    /**
     * Returns Bible text from SermonBrowser's own API for various translations.
     *
     * @param array<string, mixed> $start Start reference.
     * @param array<string, mixed> $end End reference.
     * @param string $version The Bible version code.
     * @return string The Bible text HTML.
     */
    public static function addOtherBibles(array $start, array $end, string $version): string
    {
        if ($version === 'hnv') {
            return '<div class="' . esc_attr($version) . '">'
                . '<p>Sorry, the Hebrew Names Version is no longer available.</p></div>';
        }

        $reference = str_replace(' ', '+', self::tidyReference($start, $end));
        $reference = str_replace('Psalm+', 'Psalms+', $reference); // Fix for API "Psalms" vs "Psalm"
        $oldChapter = isset($start['chapter']) ? (string) $start['chapter'] : '';

        $url = "http://api.preachingcentral.com/bible.php?passage={$reference}&version={$version}";
        $pageContent = self::downloadPage($url);

        if ($pageContent === null) {
            return '';
        }

        $xml = self::getXml($pageContent);
        $output = '';

        if (isset($xml->range->item)) {
            foreach ($xml->range->item as $item) {
                $text = (string) $item->text;
                if ($text !== '[[EMPTY]]') {
                    $itemChapter = (string) $item->chapter;
                    $itemVerse = (string) $item->verse;

                    if ($oldChapter === $itemChapter) {
                        $output .= " <span class=\"verse-num\">{$itemVerse}</span>";
                    } else {
                        $output .= " <span class=\"chapter-num\">{$itemChapter}:{$itemVerse}</span> ";
                        $oldChapter = $itemChapter;
                    }
                    $output .= $text;
                }
            }
        }

        $tidyRef = self::tidyReference($start, $end);
        return '<div class="' . esc_attr($version) . '"><h2>' . $tidyRef . '</h2><p>' . $output
            . ' (<a href="http://biblepro.bibleocean.com/dox/default.aspx">'
            . strtoupper($version) . '</a>)</p></div>';
    }
}
