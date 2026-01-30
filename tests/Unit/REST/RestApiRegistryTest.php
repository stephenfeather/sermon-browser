<?php
/**
 * Tests for RestApiRegistry.
 *
 * @package SermonBrowser\Tests\Unit\REST
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\REST;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\REST\RestApiRegistry;
use SermonBrowser\REST\RestController;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

/**
 * Test RestApiRegistry functionality.
 *
 * Tests the REST API registry that hooks controllers into WordPress.
 */
class RestApiRegistryTest extends TestCase
{
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset singleton between tests.
        RestApiRegistry::reset();
    }

    /**
     * Test getInstance returns singleton.
     */
    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = RestApiRegistry::getInstance();
        $instance2 = RestApiRegistry::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test reset clears singleton.
     */
    public function testResetClearsSingleton(): void
    {
        $instance1 = RestApiRegistry::getInstance();
        RestApiRegistry::reset();
        $instance2 = RestApiRegistry::getInstance();

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test addController accepts RestController instance.
     */
    public function testAddControllerAcceptsRestController(): void
    {
        $controller = $this->createMockController();
        $registry = RestApiRegistry::getInstance();

        $result = $registry->addController($controller);

        $this->assertSame($registry, $result);
    }

    /**
     * Test addController allows chaining.
     */
    public function testAddControllerAllowsChaining(): void
    {
        $controller1 = $this->createMockController();
        $controller2 = $this->createMockController();
        $registry = RestApiRegistry::getInstance();

        $result = $registry
            ->addController($controller1)
            ->addController($controller2);

        $this->assertSame($registry, $result);
    }

    /**
     * Test init hooks rest_api_init action.
     */
    public function testInitHooksRestApiInit(): void
    {
        Actions\expectAdded('rest_api_init')
            ->once()
            ->with(\Mockery::type('callable'));

        $registry = RestApiRegistry::getInstance();
        $registry->init();
    }

    /**
     * Test register calls register_routes on all controllers.
     */
    public function testRegisterCallsRegisterRoutesOnAllControllers(): void
    {
        $controller1 = $this->createMockController();
        $controller1->shouldReceive('register_routes')->once();

        $controller2 = $this->createMockController();
        $controller2->shouldReceive('register_routes')->once();

        $registry = RestApiRegistry::getInstance();
        $registry->addController($controller1);
        $registry->addController($controller2);
        $registry->register();
    }

    /**
     * Test register with no controllers does not error.
     */
    public function testRegisterWithNoControllersDoesNotError(): void
    {
        $registry = RestApiRegistry::getInstance();

        // Should not throw an exception.
        $registry->register();

        $this->assertTrue(true);
    }

    /**
     * Test getControllers returns empty array initially.
     */
    public function testGetControllersReturnsEmptyArrayInitially(): void
    {
        $registry = RestApiRegistry::getInstance();

        $controllers = $registry->getControllers();

        $this->assertIsArray($controllers);
        $this->assertEmpty($controllers);
    }

    /**
     * Test getControllers returns added controllers.
     */
    public function testGetControllersReturnsAddedControllers(): void
    {
        $controller1 = $this->createMockController();
        $controller2 = $this->createMockController();

        $registry = RestApiRegistry::getInstance();
        $registry->addController($controller1);
        $registry->addController($controller2);

        $controllers = $registry->getControllers();

        $this->assertCount(2, $controllers);
        $this->assertSame($controller1, $controllers[0]);
        $this->assertSame($controller2, $controllers[1]);
    }

    /**
     * Create a mock controller for testing.
     *
     * @return \Mockery\MockInterface|RestController
     */
    private function createMockController()
    {
        return \Mockery::mock(RestController::class);
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        RestApiRegistry::reset();
        parent::tearDown();
    }
}
