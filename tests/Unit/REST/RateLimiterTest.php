<?php

/**
 * Tests for RateLimiter.
 *
 * @package SermonBrowser\Tests\Unit\REST
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\RateLimiter;
use SermonBrowser\Constants;
use Brain\Monkey\Functions;
use Mockery;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Test RateLimiter functionality.
 *
 * Tests the rate limiting logic for REST API endpoints.
 */
class RateLimiterTest extends TestCase
{
    /**
     * The rate limiter instance.
     *
     * @var RateLimiter
     */
    private RateLimiter $rateLimiter;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        // Call grandparent setUp directly to avoid TestCase's default stubs.
        \PHPUnit\Framework\TestCase::setUp();
        \Brain\Monkey\setUp();

        $this->rateLimiter = new RateLimiter();

        // Define escaping and translation functions (needed by error messages).
        Functions\stubs([
            '__' => static fn($text, $domain = 'default') => $text,
            'wp_unslash' => static fn($value) => $value,
            'sanitize_text_field' => static fn($text) => trim(strip_tags((string) $text)),
        ]);
    }

    /**
     * Tear down the test.
     */
    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        \PHPUnit\Framework\TestCase::tearDown();
    }

    /**
     * Create a mock WP_REST_Request.
     *
     * @param string|null $forwardedFor X-Forwarded-For header value.
     * @param string|null $realIp X-Real-IP header value.
     * @return WP_REST_Request The mock request.
     */
    private function createMockRequest(?string $forwardedFor = null, ?string $realIp = null): WP_REST_Request
    {
        $request = Mockery::mock(WP_REST_Request::class);
        $request->shouldReceive('get_header')
            ->with('X-Forwarded-For')
            ->andReturn($forwardedFor);
        $request->shouldReceive('get_header')
            ->with('X-Real-IP')
            ->andReturn($realIp);

        return $request;
    }

    /**
     * Test check allows request when under limit.
     */
    public function testCheckAllowsRequestWhenUnderLimit(): void
    {
        $request = $this->createMockRequest();
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn(false);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->with('sermon_browser_rate_limit_ip', '192.168.1.1', $request)
            ->andReturn('192.168.1.1');

        $result = $this->rateLimiter->check($request);

        $this->assertTrue($result);
    }

    /**
     * Test check returns error when over limit.
     */
    public function testCheckReturnsErrorWhenOverLimit(): void
    {
        $request = $this->createMockRequest();
        $_SERVER['REMOTE_ADDR'] = '192.168.1.2';

        $now = time();
        $existingData = [
            'count' => Constants::RATE_LIMIT_ANONYMOUS,
            'reset' => $now + 30,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.2');

        $result = $this->rateLimiter->check($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_rate_limit_exceeded', $result->get_error_code());

        $errorData = $result->get_error_data();
        $this->assertEquals(429, $errorData['status']);
    }

    /**
     * Test check uses correct limit for anonymous users.
     */
    public function testCheckUsesCorrectLimitForAnonymousUsers(): void
    {
        $request = $this->createMockRequest();
        $_SERVER['REMOTE_ADDR'] = '192.168.1.3';

        $now = time();
        $existingData = [
            'count' => Constants::RATE_LIMIT_ANONYMOUS - 1, // One request below limit.
            'reset' => $now + 30,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.3');

        $result = $this->rateLimiter->check($request);

        $this->assertTrue($result);
    }

    /**
     * Test check uses correct limit for authenticated users.
     */
    public function testCheckUsesCorrectLimitForAuthenticatedUsers(): void
    {
        $request = $this->createMockRequest();
        $_SERVER['REMOTE_ADDR'] = '192.168.1.4';

        $now = time();
        // Authenticated users have higher limit.
        $existingData = [
            'count' => Constants::RATE_LIMIT_ANONYMOUS + 10, // Above anonymous limit but below authenticated.
            'reset' => $now + 30,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(true);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.4');

        $result = $this->rateLimiter->check($request);

        $this->assertTrue($result);
    }

    /**
     * Test check uses stricter limits for search endpoint (anonymous).
     */
    public function testCheckUsesStricterLimitForSearchEndpointAnonymous(): void
    {
        $request = $this->createMockRequest();
        $_SERVER['REMOTE_ADDR'] = '192.168.1.5';

        $now = time();
        $existingData = [
            'count' => Constants::RATE_LIMIT_SEARCH_ANONYMOUS, // At search limit.
            'reset' => $now + 30,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.5');

        $result = $this->rateLimiter->check($request, true);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Test check resets counter when window expires.
     */
    public function testCheckResetsCounterWhenWindowExpires(): void
    {
        $request = $this->createMockRequest();
        $_SERVER['REMOTE_ADDR'] = '192.168.1.6';

        $now = time();
        // Expired window.
        $existingData = [
            'count' => Constants::RATE_LIMIT_ANONYMOUS + 100,
            'reset' => $now - 10, // Expired 10 seconds ago.
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.6');

        $result = $this->rateLimiter->check($request);

        $this->assertTrue($result);
    }

    /**
     * Test addHeaders adds rate limit headers.
     */
    public function testAddHeadersAddsRateLimitHeaders(): void
    {
        $request = $this->createMockRequest();
        $response = new WP_REST_Response(['data' => 'test']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.7';

        $now = time();
        $existingData = [
            'count' => 5,
            'reset' => $now + 30,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.7');

        $result = $this->rateLimiter->addHeaders($response, $request);

        $headers = $result->get_headers();
        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /**
     * Test addHeaders calculates remaining correctly.
     */
    public function testAddHeadersCalculatesRemainingCorrectly(): void
    {
        $request = $this->createMockRequest();
        $response = new WP_REST_Response(['data' => 'test']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.8';

        $now = time();
        $existingData = [
            'count' => 10,
            'reset' => $now + 30,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.8');

        $result = $this->rateLimiter->addHeaders($response, $request);

        $headers = $result->get_headers();
        $this->assertEquals(Constants::RATE_LIMIT_ANONYMOUS, $headers['X-RateLimit-Limit']);
        $this->assertEquals(Constants::RATE_LIMIT_ANONYMOUS - 10, $headers['X-RateLimit-Remaining']);
    }

    /**
     * Test addHeaders shows zero remaining when at limit.
     */
    public function testAddHeadersShowsZeroRemainingWhenAtLimit(): void
    {
        $request = $this->createMockRequest();
        $response = new WP_REST_Response(['data' => 'test']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.9';

        $now = time();
        $existingData = [
            'count' => Constants::RATE_LIMIT_ANONYMOUS + 10, // Over limit.
            'reset' => $now + 30,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.9');

        $result = $this->rateLimiter->addHeaders($response, $request);

        $headers = $result->get_headers();
        $this->assertEquals(0, $headers['X-RateLimit-Remaining']);
    }

    /**
     * Test getLimit returns correct limit for anonymous standard endpoint.
     */
    public function testGetLimitReturnsCorrectLimitForAnonymousStandard(): void
    {
        $limit = $this->rateLimiter->getLimit(false, false);

        $this->assertEquals(Constants::RATE_LIMIT_ANONYMOUS, $limit);
    }

    /**
     * Test getLimit returns correct limit for authenticated standard endpoint.
     */
    public function testGetLimitReturnsCorrectLimitForAuthenticatedStandard(): void
    {
        $limit = $this->rateLimiter->getLimit(true, false);

        $this->assertEquals(Constants::RATE_LIMIT_AUTHENTICATED, $limit);
    }

    /**
     * Test getLimit returns correct limit for anonymous search endpoint.
     */
    public function testGetLimitReturnsCorrectLimitForAnonymousSearch(): void
    {
        $limit = $this->rateLimiter->getLimit(false, true);

        $this->assertEquals(Constants::RATE_LIMIT_SEARCH_ANONYMOUS, $limit);
    }

    /**
     * Test getLimit returns correct limit for authenticated search endpoint.
     */
    public function testGetLimitReturnsCorrectLimitForAuthenticatedSearch(): void
    {
        $limit = $this->rateLimiter->getLimit(true, true);

        $this->assertEquals(Constants::RATE_LIMIT_SEARCH_AUTHENTICATED, $limit);
    }

    /**
     * Test check uses X-Forwarded-For header when present.
     */
    public function testCheckUsesXForwardedForHeader(): void
    {
        $request = $this->createMockRequest('203.0.113.50, 198.51.100.1');
        $_SERVER['REMOTE_ADDR'] = '192.168.1.10';

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn(false);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->with('sermon_browser_rate_limit_ip', '203.0.113.50', $request)
            ->andReturn('203.0.113.50');

        $result = $this->rateLimiter->check($request);

        $this->assertTrue($result);
    }

    /**
     * Test check uses X-Real-IP header when X-Forwarded-For is not present.
     */
    public function testCheckUsesXRealIpHeader(): void
    {
        $request = $this->createMockRequest(null, '198.51.100.25');
        $_SERVER['REMOTE_ADDR'] = '192.168.1.11';

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn(false);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->with('sermon_browser_rate_limit_ip', '198.51.100.25', $request)
            ->andReturn('198.51.100.25');

        $result = $this->rateLimiter->check($request);

        $this->assertTrue($result);
    }

    /**
     * Test reset clears the rate limit for an IP.
     */
    public function testResetClearsRateLimitForIp(): void
    {
        Functions\expect('delete_transient')
            ->once()
            ->andReturn(true);

        $result = $this->rateLimiter->reset('192.168.1.100');

        $this->assertTrue($result);
    }

    /**
     * Test reset clears search endpoint rate limit.
     */
    public function testResetClearsSearchEndpointRateLimit(): void
    {
        Functions\expect('delete_transient')
            ->once()
            ->andReturn(true);

        $result = $this->rateLimiter->reset('192.168.1.100', true);

        $this->assertTrue($result);
    }

    /**
     * Test check error includes retry_after in data.
     */
    public function testCheckErrorIncludesRetryAfterInData(): void
    {
        $request = $this->createMockRequest();
        $_SERVER['REMOTE_ADDR'] = '192.168.1.12';

        $now = time();
        $resetTime = $now + 45;
        $existingData = [
            'count' => Constants::RATE_LIMIT_ANONYMOUS + 1,
            'reset' => $resetTime,
        ];

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn($existingData);

        Functions\expect('set_transient')
            ->once()
            ->andReturn(true);

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.12');

        $result = $this->rateLimiter->check($request);

        $this->assertInstanceOf(WP_Error::class, $result);
        $errorData = $result->get_error_data();
        $this->assertArrayHasKey('retry_after', $errorData);
        $this->assertGreaterThan(0, $errorData['retry_after']);
    }

    /**
     * Test addHeaders with fresh window shows full limit as remaining.
     */
    public function testAddHeadersWithFreshWindowShowsFullRemaining(): void
    {
        $request = $this->createMockRequest();
        $response = new WP_REST_Response(['data' => 'test']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.13';

        Functions\expect('is_user_logged_in')
            ->once()
            ->andReturn(false);

        Functions\expect('get_transient')
            ->once()
            ->andReturn(false); // No existing data.

        Functions\expect('apply_filters')
            ->once()
            ->andReturn('192.168.1.13');

        $result = $this->rateLimiter->addHeaders($response, $request);

        $headers = $result->get_headers();
        $this->assertEquals(Constants::RATE_LIMIT_ANONYMOUS, $headers['X-RateLimit-Remaining']);
    }
}
