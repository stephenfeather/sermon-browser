<?php

/**
 * Tests for Frontend\BibleText class.
 *
 * @package SermonBrowser\Tests\Unit\Frontend
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Frontend;

use Brain\Monkey\Functions;
use Mockery;
use SermonBrowser\Config\Defaults;
use SermonBrowser\Config\OptionsManager;
use SermonBrowser\Frontend\BibleText;
use SermonBrowser\Frontend\UrlBuilder;
use SermonBrowser\Tests\TestCase;
use SimpleXMLElement;

/**
 * Test class for BibleText.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class BibleTextTest extends TestCase
{
    /**
     * Helper to create a start reference array.
     *
     * @param string $book    Book name.
     * @param string $chapter Chapter number.
     * @param string $verse   Verse number.
     * @return array<string, string>
     */
    private function makeRef(string $book, string $chapter, string $verse): array
    {
        return ['book' => $book, 'chapter' => $chapter, 'verse' => $verse];
    }

    /**
     * Mock Defaults::get to return Bible book arrays.
     */
    private function mockDefaults(): void
    {
        $engBooks = [
            'Genesis', 'Exodus', 'Leviticus', 'Numbers', 'Deuteronomy',
            'Joshua', 'Judges', 'Ruth', '1 Samuel', '2 Samuel',
            '1 Kings', '2 Kings', '1 Chronicles', '2 Chronicles',
            'Ezra', 'Nehemiah', 'Esther', 'Job', 'Psalm', 'Proverbs',
            'Ecclesiastes', 'Song of Solomon', 'Isaiah', 'Jeremiah',
            'Lamentations', 'Ezekiel', 'Daniel', 'Hosea', 'Joel', 'Amos',
            'Obadiah', 'Jonah', 'Micah', 'Nahum', 'Habakkuk', 'Zephaniah',
            'Haggai', 'Zechariah', 'Malachi', 'Matthew', 'Mark', 'Luke',
            'John', 'Acts', 'Romans', '1 Corinthians', '2 Corinthians',
            'Galatians', 'Ephesians', 'Philippians', 'Colossians',
            '1 Thessalonians', '2 Thessalonians', '1 Timothy', '2 Timothy',
            'Titus', 'Philemon', 'Hebrews', 'James', '1 Peter', '2 Peter',
            '1 John', '2 John', '3 John', 'Jude', 'Revelation',
        ];

        $defaults = Mockery::mock('alias:' . Defaults::class);
        $defaults->shouldReceive('get')
            ->with('eng_bible_books')
            ->andReturn($engBooks);
        $defaults->shouldReceive('get')
            ->with('bible_books')
            ->andReturn($engBooks); // Same for tests (no translation)
    }

    // =========================================================================
    // downloadPage tests
    // =========================================================================

    /**
     * Test downloadPage returns body on successful response.
     */
    public function testDownloadPageReturnsBodyOnSuccess(): void
    {
        $expectedBody = '<html>Content</html>';

        Functions\expect('wp_remote_get')
            ->once()
            ->with('https://example.com', ['headers' => []])
            ->andReturn(['body' => $expectedBody, 'response' => ['code' => 200]]);

        $result = BibleText::downloadPage('https://example.com');

        $this->assertEquals($expectedBody, $result);
    }

    /**
     * Test downloadPage with string header converts to Authorization header.
     */
    public function testDownloadPageWithStringHeaderConvertsToAuthorization(): void
    {
        $token = 'Token abc123';

        Functions\expect('wp_remote_get')
            ->once()
            ->with('https://api.example.com', ['headers' => ['Authorization' => $token]])
            ->andReturn(['body' => 'OK']);

        $result = BibleText::downloadPage('https://api.example.com', $token);

        $this->assertEquals('OK', $result);
    }

    /**
     * Test downloadPage with array headers passes them directly.
     */
    public function testDownloadPageWithArrayHeaders(): void
    {
        $headers = ['Accept' => 'application/json', 'X-Custom' => 'value'];

        Functions\expect('wp_remote_get')
            ->once()
            ->with('https://api.example.com', ['headers' => $headers])
            ->andReturn(['body' => '{"data": true}']);

        $result = BibleText::downloadPage('https://api.example.com', $headers);

        $this->assertEquals('{"data": true}', $result);
    }

    /**
     * Test downloadPage returns null on WP_Error.
     */
    public function testDownloadPageReturnsNullOnWpError(): void
    {
        $wpError = new \stdClass();
        $wpError->errors = ['http_request_failed' => ['Connection timed out']];

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn($wpError);

        $result = BibleText::downloadPage('https://example.com');

        $this->assertNull($result);
    }

    /**
     * Test downloadPage returns null when body is missing.
     */
    public function testDownloadPageReturnsNullWhenBodyMissing(): void
    {
        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['response' => ['code' => 200]]);

        $result = BibleText::downloadPage('https://example.com');

        $this->assertNull($result);
    }

    // =========================================================================
    // tidyReference tests
    // =========================================================================

    /**
     * Test tidyReference returns empty string when start book is empty.
     */
    public function testTidyReferenceReturnsEmptyForEmptyBook(): void
    {
        $this->mockDefaults();

        $result = BibleText::tidyReference(
            ['book' => '', 'chapter' => '1', 'verse' => '1'],
            ['book' => '', 'chapter' => '1', 'verse' => '1']
        );

        $this->assertEquals('', $result);
    }

    /**
     * Test tidyReference formats single verse correctly.
     */
    public function testTidyReferenceSingleVerse(): void
    {
        $this->mockDefaults();

        $result = BibleText::tidyReference(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertEquals('John 3:16', $result);
    }

    /**
     * Test tidyReference formats verse range in same chapter.
     */
    public function testTidyReferenceVerseRangeSameChapter(): void
    {
        $this->mockDefaults();

        $result = BibleText::tidyReference(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '21')
        );

        $this->assertEquals('John 3:16-21', $result);
    }

    /**
     * Test tidyReference formats chapter range in same book.
     */
    public function testTidyReferenceChapterRangeSameBook(): void
    {
        $this->mockDefaults();

        $result = BibleText::tidyReference(
            $this->makeRef('Romans', '8', '1'),
            $this->makeRef('Romans', '9', '5')
        );

        $this->assertEquals('Romans 8:1-9:5', $result);
    }

    /**
     * Test tidyReference formats range across different books.
     */
    public function testTidyReferenceRangeAcrossBooks(): void
    {
        $this->mockDefaults();

        $result = BibleText::tidyReference(
            $this->makeRef('Genesis', '1', '1'),
            $this->makeRef('Exodus', '2', '10')
        );

        $this->assertEquals('Genesis 1:1 - Exodus 2:10', $result);
    }

    /**
     * Test tidyReference with addLink generates anchor tags.
     */
    public function testTidyReferenceWithAddLink(): void
    {
        $this->mockDefaults();

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        // Called twice: once for start book, once for end book (even if same)
        $urlBuilder->shouldReceive('bookLink')
            ->with('John')
            ->twice()
            ->andReturn('/?book=John');

        $result = BibleText::tidyReference(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '21'),
            true
        );

        $this->assertStringContainsString('<a href="/?book=John">John</a>', $result);
        $this->assertStringContainsString('3:16-21', $result);
    }

    /**
     * Test tidyReference with addLink for range across books.
     */
    public function testTidyReferenceWithAddLinkAcrossBooks(): void
    {
        $this->mockDefaults();

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('bookLink')
            ->with('Genesis')
            ->once()
            ->andReturn('/?book=Genesis');
        $urlBuilder->shouldReceive('bookLink')
            ->with('Exodus')
            ->once()
            ->andReturn('/?book=Exodus');

        $result = BibleText::tidyReference(
            $this->makeRef('Genesis', '1', '1'),
            $this->makeRef('Exodus', '2', '10'),
            true
        );

        $this->assertStringContainsString('<a href="/?book=Genesis">Genesis</a>', $result);
        $this->assertStringContainsString('<a href="/?book=Exodus">Exodus</a>', $result);
    }

    /**
     * Test tidyReference handles missing keys gracefully.
     */
    public function testTidyReferenceHandlesMissingKeys(): void
    {
        $this->mockDefaults();

        $result = BibleText::tidyReference(
            ['book' => 'John'],
            ['book' => 'John']
        );

        $this->assertEquals('John :', $result);
    }

    /**
     * Test tidyReference trims whitespace from values.
     */
    public function testTidyReferenceTrimsWhitespace(): void
    {
        $this->mockDefaults();

        $result = BibleText::tidyReference(
            ['book' => '  John  ', 'chapter' => ' 3 ', 'verse' => ' 16 '],
            ['book' => '  John  ', 'chapter' => ' 3 ', 'verse' => ' 21 ']
        );

        $this->assertEquals('John 3:16-21', $result);
    }

    // =========================================================================
    // printBiblePassage tests
    // =========================================================================

    /**
     * Test printBiblePassage outputs correct HTML.
     */
    public function testPrintBiblePassageOutputsHtml(): void
    {
        $this->mockDefaults();

        $this->expectOutputString("<p class='bible-passage'>John 3:16-21</p>");

        BibleText::printBiblePassage(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '21')
        );
    }

    // =========================================================================
    // getBooks tests
    // =========================================================================

    /**
     * Test getBooks returns reference with links.
     */
    public function testGetBooksReturnsReferenceWithLinks(): void
    {
        $this->mockDefaults();

        $urlBuilder = Mockery::mock('alias:' . UrlBuilder::class);
        $urlBuilder->shouldReceive('bookLink')
            ->with('Matthew')
            ->andReturn('/?book=Matthew');

        $result = BibleText::getBooks(
            $this->makeRef('Matthew', '5', '1'),
            $this->makeRef('Matthew', '7', '29')
        );

        $this->assertStringContainsString('<a href="/?book=Matthew">Matthew</a>', $result);
    }

    // =========================================================================
    // addBibleText tests
    // =========================================================================

    /**
     * Test addBibleText routes to ESV for esv version.
     */
    public function testAddBibleTextRoutesToEsvForEsvVersion(): void
    {
        $this->mockDefaults();

        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->with('esv_api_key')
            ->andReturn('');

        // No ESV key, so it falls back to KJV (addOtherBibles)
        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(null);

        $result = BibleText::addBibleText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'esv'
        );

        // Falls back to KJV, returns empty on failure
        $this->assertEquals('', $result);
    }

    /**
     * Test addBibleText routes to NET for net version.
     */
    public function testAddBibleTextRoutesToNetForNetVersion(): void
    {
        $this->mockDefaults();

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(null);

        $result = BibleText::addBibleText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'net'
        );

        $this->assertEquals('', $result);
    }

    /**
     * Test addBibleText routes to other bibles for other versions.
     */
    public function testAddBibleTextRoutesToOtherBiblesForOtherVersions(): void
    {
        $this->mockDefaults();

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(null);

        $result = BibleText::addBibleText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'kjv'
        );

        $this->assertEquals('', $result);
    }

    // =========================================================================
    // addEsvText tests
    // =========================================================================

    /**
     * Test addEsvText returns ESV passage when API key exists and API succeeds.
     */
    public function testAddEsvTextReturnsPassageOnSuccess(): void
    {
        $this->mockDefaults();

        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->with('esv_api_key')
            ->andReturn('test-api-key');

        $apiResponse = json_encode([
            'passages' => ['<p>For God so loved the world...</p>']
        ]);

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturnUsing(function ($url, $args) use ($apiResponse) {
                $this->assertStringContainsString('api.esv.org', $url);
                $this->assertStringContainsString('John%203%3A16', $url);
                $this->assertEquals('Token test-api-key', $args['headers']['Authorization']);
                return ['body' => $apiResponse];
            });

        $result = BibleText::addEsvText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertEquals('<p>For God so loved the world...</p>', $result);
    }

    /**
     * Test addEsvText falls back to KJV when no API key.
     */
    public function testAddEsvTextFallsBackToKjvWhenNoApiKey(): void
    {
        $this->mockDefaults();

        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->with('esv_api_key')
            ->andReturn('');

        // KJV fallback call
        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(null);

        $result = BibleText::addEsvText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        // Empty because KJV API also returns null
        $this->assertEquals('', $result);
    }

    /**
     * Test addEsvText falls back to KJV when API returns invalid JSON.
     */
    public function testAddEsvTextFallsBackToKjvOnInvalidJson(): void
    {
        $this->mockDefaults();

        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->with('esv_api_key')
            ->andReturn('test-api-key');

        // First call: ESV API returns invalid JSON
        // Second call: KJV fallback
        Functions\expect('wp_remote_get')
            ->twice()
            ->andReturn(['body' => 'not valid json'], null);

        $result = BibleText::addEsvText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertEquals('', $result);
    }

    /**
     * Test addEsvText falls back when passages array is missing.
     */
    public function testAddEsvTextFallsBackWhenPassagesMissing(): void
    {
        $this->mockDefaults();

        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->with('esv_api_key')
            ->andReturn('test-api-key');

        Functions\expect('wp_remote_get')
            ->twice()
            ->andReturn(['body' => '{"error": "not found"}'], null);

        $result = BibleText::addEsvText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertEquals('', $result);
    }

    /**
     * Test addEsvText combines multiple passages.
     */
    public function testAddEsvTextCombinesMultiplePassages(): void
    {
        $this->mockDefaults();

        $optionsManager = Mockery::mock('alias:' . OptionsManager::class);
        $optionsManager->shouldReceive('get')
            ->with('esv_api_key')
            ->andReturn('test-api-key');

        $apiResponse = json_encode([
            'passages' => ['<p>Part 1</p>', '<p>Part 2</p>']
        ]);

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $apiResponse]);

        $result = BibleText::addEsvText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '21')
        );

        $this->assertEquals('<p>Part 1</p><p>Part 2</p>', $result);
    }

    // =========================================================================
    // getXml tests
    // =========================================================================

    /**
     * Test getXml returns SimpleXMLElement.
     */
    public function testGetXmlReturnsSimpleXmlElement(): void
    {
        $xml = '<root><item>test</item></root>';

        $result = BibleText::getXml($xml);

        $this->assertInstanceOf(SimpleXMLElement::class, $result);
        $this->assertEquals('test', (string) $result->item);
    }

    /**
     * Test getXml throws exception for invalid XML.
     */
    public function testGetXmlThrowsExceptionForInvalidXml(): void
    {
        $this->expectException(\Throwable::class);

        // Suppress libxml errors to allow the exception to be thrown cleanly
        $previousUseErrors = libxml_use_internal_errors(true);
        try {
            BibleText::getXml('not valid xml');
        } finally {
            libxml_use_internal_errors($previousUseErrors);
        }
    }

    // =========================================================================
    // addNetText tests
    // =========================================================================

    /**
     * Test addNetText returns formatted NET Bible text.
     */
    public function testAddNetTextReturnsFormattedText(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?>
            <bible>
                <item>
                    <chapter>3</chapter>
                    <verse>16</verse>
                    <text>For this is the way God loved the world</text>
                </item>
            </bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturnUsing(function ($url) use ($xmlResponse) {
                $this->assertStringContainsString('labs.bible.org', $url);
                $this->assertStringContainsString('John+3:16', $url);
                return ['body' => $xmlResponse];
            });

        $result = BibleText::addNetText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertStringContainsString('class="net"', $result);
        $this->assertStringContainsString('John 3:16', $result);
        $this->assertStringContainsString('NET Bible', $result);
        $this->assertStringContainsString('For this is the way God loved the world', $result);
    }

    /**
     * Test addNetText returns empty on API failure.
     */
    public function testAddNetTextReturnsEmptyOnApiFailure(): void
    {
        $this->mockDefaults();

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(null);

        $result = BibleText::addNetText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertEquals('', $result);
    }

    /**
     * Test addNetText skips EMPTY verses.
     */
    public function testAddNetTextSkipsEmptyVerses(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?>
            <bible>
                <item>
                    <chapter>3</chapter>
                    <verse>16</verse>
                    <text>[[EMPTY]]</text>
                </item>
                <item>
                    <chapter>3</chapter>
                    <verse>17</verse>
                    <text>For God did not send his Son</text>
                </item>
            </bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addNetText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '17')
        );

        $this->assertStringNotContainsString('[[EMPTY]]', $result);
        $this->assertStringContainsString('For God did not send his Son', $result);
    }

    /**
     * Test addNetText handles chapter transitions.
     */
    public function testAddNetTextHandlesChapterTransitions(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?>
            <bible>
                <item>
                    <chapter>3</chapter>
                    <verse>36</verse>
                    <text>End of chapter 3</text>
                </item>
                <item>
                    <chapter>4</chapter>
                    <verse>1</verse>
                    <text>Start of chapter 4</text>
                </item>
            </bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addNetText(
            $this->makeRef('John', '3', '36'),
            $this->makeRef('John', '4', '1')
        );

        $this->assertStringContainsString('class="verse-num">36</span>', $result);
        $this->assertStringContainsString('class="chapter-num">4:1</span>', $result);
    }

    /**
     * Test addNetText handles paragraph tags in text.
     */
    public function testAddNetTextHandlesParagraphTags(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?>
            <bible>
                <item>
                    <chapter>3</chapter>
                    <verse>16</verse>
                    <text><![CDATA[<p class="poetry">For this is the way God loved</p>]]></text>
                </item>
            </bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addNetText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertStringContainsString('<p class="poetry">', $result);
    }

    /**
     * Test addNetText handles empty XML items.
     */
    public function testAddNetTextHandlesEmptyXmlItems(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?><bible></bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addNetText(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16')
        );

        $this->assertStringContainsString('class="net"', $result);
        $this->assertStringContainsString('John 3:16', $result);
    }

    // =========================================================================
    // addOtherBibles tests
    // =========================================================================

    /**
     * Test addOtherBibles returns unavailable message for HNV version.
     */
    public function testAddOtherBiblesReturnsUnavailableForHnv(): void
    {
        $this->mockDefaults();

        $result = BibleText::addOtherBibles(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'hnv'
        );

        $this->assertStringContainsString('class="hnv"', $result);
        $this->assertStringContainsString('Hebrew Names Version is no longer available', $result);
    }

    /**
     * Test addOtherBibles returns formatted KJV text.
     */
    public function testAddOtherBiblesReturnsFormattedKjvText(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?>
            <bible>
                <range>
                    <item>
                        <chapter>3</chapter>
                        <verse>16</verse>
                        <text>For God so loved the world</text>
                    </item>
                </range>
            </bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturnUsing(function ($url) use ($xmlResponse) {
                $this->assertStringContainsString('api.preachingcentral.com', $url);
                $this->assertStringContainsString('version=kjv', $url);
                return ['body' => $xmlResponse];
            });

        $result = BibleText::addOtherBibles(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'kjv'
        );

        $this->assertStringContainsString('class="kjv"', $result);
        $this->assertStringContainsString('John 3:16', $result);
        $this->assertStringContainsString('KJV', $result);
        $this->assertStringContainsString('For God so loved the world', $result);
    }

    /**
     * Test addOtherBibles returns empty on API failure.
     */
    public function testAddOtherBiblesReturnsEmptyOnApiFailure(): void
    {
        $this->mockDefaults();

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(null);

        $result = BibleText::addOtherBibles(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'kjv'
        );

        $this->assertEquals('', $result);
    }

    /**
     * Test addOtherBibles converts Psalm to Psalms for API.
     */
    public function testAddOtherBiblesConvertsPsalmToPsalms(): void
    {
        $this->mockDefaults();

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturnUsing(function ($url) {
                $this->assertStringContainsString('Psalms+23', $url);
                return null;
            });

        BibleText::addOtherBibles(
            $this->makeRef('Psalm', '23', '1'),
            $this->makeRef('Psalm', '23', '6'),
            'kjv'
        );
    }

    /**
     * Test addOtherBibles skips EMPTY verses.
     */
    public function testAddOtherBiblesSkipsEmptyVerses(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?>
            <bible>
                <range>
                    <item>
                        <chapter>3</chapter>
                        <verse>16</verse>
                        <text>[[EMPTY]]</text>
                    </item>
                    <item>
                        <chapter>3</chapter>
                        <verse>17</verse>
                        <text>For God sent not his Son</text>
                    </item>
                </range>
            </bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addOtherBibles(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '17'),
            'kjv'
        );

        $this->assertStringNotContainsString('[[EMPTY]]', $result);
        $this->assertStringContainsString('For God sent not his Son', $result);
    }

    /**
     * Test addOtherBibles handles chapter transitions.
     */
    public function testAddOtherBiblesHandlesChapterTransitions(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?>
            <bible>
                <range>
                    <item>
                        <chapter>3</chapter>
                        <verse>36</verse>
                        <text>End of chapter 3</text>
                    </item>
                    <item>
                        <chapter>4</chapter>
                        <verse>1</verse>
                        <text>Start of chapter 4</text>
                    </item>
                </range>
            </bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addOtherBibles(
            $this->makeRef('John', '3', '36'),
            $this->makeRef('John', '4', '1'),
            'kjv'
        );

        $this->assertStringContainsString('class="verse-num">36</span>', $result);
        $this->assertStringContainsString('class="chapter-num">4:1</span>', $result);
    }

    /**
     * Test addOtherBibles handles empty range.
     */
    public function testAddOtherBiblesHandlesEmptyRange(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?><bible><range></range></bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addOtherBibles(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'kjv'
        );

        $this->assertStringContainsString('class="kjv"', $result);
        $this->assertStringContainsString('John 3:16', $result);
    }

    /**
     * Test addOtherBibles escapes version in class attribute.
     */
    public function testAddOtherBiblesEscapesVersionInClass(): void
    {
        $this->mockDefaults();

        $xmlResponse = '<?xml version="1.0"?><bible><range></range></bible>';

        Functions\expect('wp_remote_get')
            ->once()
            ->andReturn(['body' => $xmlResponse]);

        $result = BibleText::addOtherBibles(
            $this->makeRef('John', '3', '16'),
            $this->makeRef('John', '3', '16'),
            'asv'
        );

        $this->assertStringContainsString('class="asv"', $result);
        $this->assertStringContainsString('ASV', $result);
    }
}
