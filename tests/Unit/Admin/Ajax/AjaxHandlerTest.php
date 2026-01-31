<?php

/**
 * Tests for AjaxHandler base class.
 *
 * @package SermonBrowser\Tests\Unit\Admin\Ajax
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Admin\Ajax;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Admin\Ajax\AjaxHandler;
use Brain\Monkey\Functions;
use ReflectionClass;

/**
 * Test AjaxHandler base functionality.
 *
 * Uses an anonymous class to test protected methods.
 */
class AjaxHandlerTest extends TestCase
{
    /**
     * Concrete implementation for testing.
     *
     * @var AjaxHandler
     */
    private AjaxHandler $handler;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create concrete implementation of abstract class.
        $this->handler = new class extends AjaxHandler {
            public function register(): void
            {
                // No-op for testing.
            }

            // Expose protected methods for testing.
            public function publicVerifyNonce(): bool
            {
                return $this->verifyNonce();
            }

            public function publicCheckCapability(): bool
            {
                return $this->checkCapability();
            }

            public function publicGetPostString(string $key, string $default = ''): string
            {
                return $this->getPostString($key, $default);
            }

            public function publicGetPostInt(string $key, int $default = 0): int
            {
                return $this->getPostInt($key, $default);
            }

            public function publicHasPost(string $key): bool
            {
                return $this->hasPost($key);
            }
        };
    }

    /**
     * Test getPostString with existing value.
     */
    public function testGetPostStringWithExistingValue(): void
    {
        $_POST['test_key'] = '  Test Value  ';

        Functions\expect('wp_unslash')
            ->once()
            ->with('  Test Value  ')
            ->andReturn('  Test Value  ');

        $result = $this->handler->publicGetPostString('test_key');

        $this->assertSame('Test Value', $result);
    }

    /**
     * Test getPostString returns default when key missing.
     */
    public function testGetPostStringReturnsDefaultWhenMissing(): void
    {
        unset($_POST['missing_key']);

        $result = $this->handler->publicGetPostString('missing_key', 'default_value');

        $this->assertSame('default_value', $result);
    }

    /**
     * Test getPostInt with existing value.
     */
    public function testGetPostIntWithExistingValue(): void
    {
        $_POST['id'] = '42';

        $result = $this->handler->publicGetPostInt('id');

        $this->assertSame(42, $result);
    }

    /**
     * Test getPostInt with string value.
     */
    public function testGetPostIntWithStringValue(): void
    {
        $_POST['id'] = 'not_a_number';

        $result = $this->handler->publicGetPostInt('id');

        $this->assertSame(0, $result);
    }

    /**
     * Test getPostInt returns default when missing.
     */
    public function testGetPostIntReturnsDefaultWhenMissing(): void
    {
        unset($_POST['missing_key']);

        $result = $this->handler->publicGetPostInt('missing_key', 99);

        $this->assertSame(99, $result);
    }

    /**
     * Test hasPost returns true when key exists.
     */
    public function testHasPostReturnsTrueWhenExists(): void
    {
        $_POST['existing_key'] = 'value';

        $this->assertTrue($this->handler->publicHasPost('existing_key'));
    }

    /**
     * Test hasPost returns false when key missing.
     */
    public function testHasPostReturnsFalseWhenMissing(): void
    {
        unset($_POST['missing_key']);

        $this->assertFalse($this->handler->publicHasPost('missing_key'));
    }

    /**
     * Test verifyNonce with valid nonce from POST.
     */
    public function testVerifyNonceWithValidNonce(): void
    {
        $_POST['_sb_nonce'] = 'valid_nonce';

        Functions\expect('wp_unslash')
            ->once()
            ->with('valid_nonce')
            ->andReturn('valid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_nonce', 'sb_ajax_nonce')
            ->andReturn(1);

        $this->assertTrue($this->handler->publicVerifyNonce());
    }

    /**
     * Test verifyNonce with invalid nonce.
     */
    public function testVerifyNonceWithInvalidNonce(): void
    {
        $_POST['_sb_nonce'] = 'invalid_nonce';

        Functions\expect('wp_unslash')
            ->once()
            ->with('invalid_nonce')
            ->andReturn('invalid_nonce');

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', 'sb_ajax_nonce')
            ->andReturn(false);

        $this->assertFalse($this->handler->publicVerifyNonce());
    }

    /**
     * Test verifyNonce with missing nonce.
     */
    public function testVerifyNonceWithMissingNonce(): void
    {
        unset($_POST['_sb_nonce']);
        unset($_GET['_sb_nonce']);

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('', 'sb_ajax_nonce')
            ->andReturn(false);

        $this->assertFalse($this->handler->publicVerifyNonce());
    }

    /**
     * Test checkCapability with capable user.
     */
    public function testCheckCapabilityWithCapableUser(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(true);

        $this->assertTrue($this->handler->publicCheckCapability());
    }

    /**
     * Test checkCapability with incapable user.
     */
    public function testCheckCapabilityWithIncapableUser(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('edit_posts')
            ->andReturn(false);

        $this->assertFalse($this->handler->publicCheckCapability());
    }

    /**
     * Clean up POST superglobal after tests.
     */
    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }
}
