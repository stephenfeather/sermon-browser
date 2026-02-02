<?php

/**
 * Coverage tests for Http\RequestInterceptor class.
 *
 * Tests the private detection methods via reflection to achieve code coverage.
 *
 * @package SermonBrowser\Tests\Unit\Http
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Http;

use ReflectionMethod;
use SermonBrowser\Tests\TestCase;
use SermonBrowser\Http\RequestInterceptor;

/**
 * Coverage test class for RequestInterceptor.
 *
 * Tests private detection methods via reflection.
 */
class RequestInterceptorCoverageTest extends TestCase
{
    /**
     * Original superglobal values for restoration.
     *
     * @var array<string, mixed>
     */
    private array $originalGet;
    private array $originalPost;
    private array $originalServer;
    private array $originalRequest;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Save original superglobals
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        $this->originalRequest = $_REQUEST;

        // Clear superglobals for clean test state
        $_GET = [];
        $_POST = [];
        $_SERVER = [];
        $_REQUEST = [];
    }

    /**
     * Restore superglobals after each test.
     */
    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;
        $_REQUEST = $this->originalRequest;

        parent::tearDown();
    }

    /**
     * Invoke a private static method for testing.
     *
     * @param string $methodName The method name to invoke.
     * @return mixed The method's return value.
     */
    private function invokePrivateMethod(string $methodName): mixed
    {
        $method = new ReflectionMethod(RequestInterceptor::class, $methodName);
        return $method->invoke(null);
    }

    // =========================================================================
    // isAjaxRequest() tests
    // =========================================================================

    /**
     * Test isAjaxRequest returns false when no POST data.
     */
    public function testIsAjaxRequestReturnsFalseWhenNoPostData(): void
    {
        $_POST = [];

        $result = $this->invokePrivateMethod('isAjaxRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isAjaxRequest returns false when sermon key missing.
     */
    public function testIsAjaxRequestReturnsFalseWhenSermonKeyMissing(): void
    {
        $_POST = ['other_key' => 'value'];

        $result = $this->invokePrivateMethod('isAjaxRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isAjaxRequest returns false when sermon is not 1.
     */
    public function testIsAjaxRequestReturnsFalseWhenSermonIsNotOne(): void
    {
        $_POST = ['sermon' => 0];

        $result = $this->invokePrivateMethod('isAjaxRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isAjaxRequest returns true when sermon equals 1.
     */
    public function testIsAjaxRequestReturnsTrueWhenSermonEqualsOne(): void
    {
        $_POST = ['sermon' => 1];

        $result = $this->invokePrivateMethod('isAjaxRequest');

        $this->assertTrue($result);
    }

    /**
     * Test isAjaxRequest returns true when sermon equals string "1".
     */
    public function testIsAjaxRequestReturnsTrueWhenSermonEqualsStringOne(): void
    {
        $_POST = ['sermon' => '1'];

        $result = $this->invokePrivateMethod('isAjaxRequest');

        $this->assertTrue($result);
    }

    // =========================================================================
    // isCssRequest() tests
    // =========================================================================

    /**
     * Test isCssRequest returns false when no relevant params.
     */
    public function testIsCssRequestReturnsFalseWhenNoRelevantParams(): void
    {
        $_SERVER = ['REQUEST_URI' => '/some-page/'];
        $_GET = [];

        $result = $this->invokePrivateMethod('isCssRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isCssRequest returns true when URL contains sb-style.css.
     */
    public function testIsCssRequestReturnsTrueWhenUrlContainsSbStyleCss(): void
    {
        $_SERVER = ['REQUEST_URI' => '/wp-content/plugins/sermon-browser/sb-style.css'];

        $result = $this->invokePrivateMethod('isCssRequest');

        $this->assertTrue($result);
    }

    /**
     * Test isCssRequest returns true with case insensitive match.
     */
    public function testIsCssRequestReturnsTrueWithCaseInsensitiveMatch(): void
    {
        $_SERVER = ['REQUEST_URI' => '/path/SB-STYLE.CSS'];

        $result = $this->invokePrivateMethod('isCssRequest');

        $this->assertTrue($result);
    }

    /**
     * Test isCssRequest returns true when sb-style GET param is set.
     */
    public function testIsCssRequestReturnsTrueWhenSbStyleGetParamSet(): void
    {
        $_SERVER = ['REQUEST_URI' => '/some-page/'];
        $_GET = ['sb-style' => '1'];

        $result = $this->invokePrivateMethod('isCssRequest');

        $this->assertTrue($result);
    }

    /**
     * Test isCssRequest handles missing REQUEST_URI.
     */
    public function testIsCssRequestHandlesMissingRequestUri(): void
    {
        $_SERVER = [];
        $_GET = [];

        $result = $this->invokePrivateMethod('isCssRequest');

        $this->assertFalse($result);
    }

    // =========================================================================
    // isDownloadRequest() tests
    // =========================================================================

    /**
     * Test isDownloadRequest returns false when download param missing.
     */
    public function testIsDownloadRequestReturnsFalseWhenDownloadParamMissing(): void
    {
        $_GET = ['file_name' => 'test.mp3'];

        $result = $this->invokePrivateMethod('isDownloadRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isDownloadRequest returns false when no file_name or url.
     */
    public function testIsDownloadRequestReturnsFalseWhenNoFileNameOrUrl(): void
    {
        $_GET = ['download' => '1'];
        $_REQUEST = [];

        $result = $this->invokePrivateMethod('isDownloadRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isDownloadRequest returns true with file_name.
     */
    public function testIsDownloadRequestReturnsTrueWithFileName(): void
    {
        $_GET = ['download' => '1', 'file_name' => 'sermon.mp3'];

        $result = $this->invokePrivateMethod('isDownloadRequest');

        $this->assertTrue($result);
    }

    /**
     * Test isDownloadRequest returns true with url in REQUEST.
     */
    public function testIsDownloadRequestReturnsTrueWithUrlInRequest(): void
    {
        $_GET = ['download' => '1'];
        $_REQUEST = ['url' => 'http://example.com/sermon.mp3'];

        $result = $this->invokePrivateMethod('isDownloadRequest');

        $this->assertTrue($result);
    }

    // =========================================================================
    // isShowRequest() tests
    // =========================================================================

    /**
     * Test isShowRequest returns false when show param missing.
     */
    public function testIsShowRequestReturnsFalseWhenShowParamMissing(): void
    {
        $_GET = ['file_name' => 'test.mp3'];

        $result = $this->invokePrivateMethod('isShowRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isShowRequest returns false when no file_name or url.
     */
    public function testIsShowRequestReturnsFalseWhenNoFileNameOrUrl(): void
    {
        $_GET = ['show' => '1'];
        $_REQUEST = [];

        $result = $this->invokePrivateMethod('isShowRequest');

        $this->assertFalse($result);
    }

    /**
     * Test isShowRequest returns true with file_name.
     */
    public function testIsShowRequestReturnsTrueWithFileName(): void
    {
        $_GET = ['show' => '1', 'file_name' => 'sermon.mp3'];

        $result = $this->invokePrivateMethod('isShowRequest');

        $this->assertTrue($result);
    }

    /**
     * Test isShowRequest returns true with url in REQUEST.
     */
    public function testIsShowRequestReturnsTrueWithUrlInRequest(): void
    {
        $_GET = ['show' => '1'];
        $_REQUEST = ['url' => 'http://example.com/sermon.mp3'];

        $result = $this->invokePrivateMethod('isShowRequest');

        $this->assertTrue($result);
    }
}
