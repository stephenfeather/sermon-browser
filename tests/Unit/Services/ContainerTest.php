<?php

/**
 * Tests for Container.
 *
 * @package SermonBrowser\Tests\Unit\Services
 */

declare(strict_types=1);

namespace SermonBrowser\Tests\Unit\Services;

use SermonBrowser\Tests\TestCase;
use SermonBrowser\Services\Container;
use SermonBrowser\Repositories\SermonRepository;
use SermonBrowser\Repositories\PreacherRepository;
use SermonBrowser\Repositories\SeriesRepository;
use SermonBrowser\Repositories\ServiceRepository;
use SermonBrowser\Repositories\FileRepository;
use SermonBrowser\Repositories\TagRepository;
use SermonBrowser\Repositories\BookRepository;
use Mockery;

/**
 * Test Container functionality.
 */
class ContainerTest extends TestCase
{
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset singleton for each test.
        Container::reset();
    }

    /**
     * Test getInstance returns singleton instance.
     */
    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = Container::getInstance();
        $instance2 = Container::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test getInstance returns Container type.
     */
    public function testGetInstanceReturnsContainerType(): void
    {
        $instance = Container::getInstance();

        $this->assertInstanceOf(Container::class, $instance);
    }

    /**
     * Test reset clears the singleton instance.
     */
    public function testResetClearsSingleton(): void
    {
        $instance1 = Container::getInstance();
        Container::reset();
        $instance2 = Container::getInstance();

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test reset can be called multiple times.
     */
    public function testResetCanBeCalledMultipleTimes(): void
    {
        Container::reset();
        Container::reset();
        Container::reset();

        $instance = Container::getInstance();

        $this->assertInstanceOf(Container::class, $instance);
    }

    /**
     * Test set allows injecting custom instance.
     */
    public function testSetAllowsInjectingCustomInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(SermonRepository::class);

        $container->set(SermonRepository::class, $mockRepository);

        $this->assertSame($mockRepository, $container->sermons());
    }

    /**
     * Test set overwrites existing instance.
     */
    public function testSetOverwritesExistingInstance(): void
    {
        $container = Container::getInstance();
        $mock1 = Mockery::mock(SermonRepository::class);
        $mock2 = Mockery::mock(SermonRepository::class);

        $container->set(SermonRepository::class, $mock1);
        $container->set(SermonRepository::class, $mock2);

        $this->assertSame($mock2, $container->sermons());
        $this->assertNotSame($mock1, $container->sermons());
    }

    /**
     * Test sermons returns SermonRepository instance.
     */
    public function testSermonsReturnsSermonRepository(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(SermonRepository::class);
        $container->set(SermonRepository::class, $mockRepository);

        $result = $container->sermons();

        $this->assertSame($mockRepository, $result);
    }

    /**
     * Test sermons returns same instance on repeated calls.
     */
    public function testSermonsReturnsCachedInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(SermonRepository::class);
        $container->set(SermonRepository::class, $mockRepository);

        $result1 = $container->sermons();
        $result2 = $container->sermons();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test preachers returns PreacherRepository instance.
     */
    public function testPreachersReturnsPreacherRepository(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(PreacherRepository::class);
        $container->set(PreacherRepository::class, $mockRepository);

        $result = $container->preachers();

        $this->assertSame($mockRepository, $result);
    }

    /**
     * Test preachers returns cached instance.
     */
    public function testPreachersReturnsCachedInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(PreacherRepository::class);
        $container->set(PreacherRepository::class, $mockRepository);

        $result1 = $container->preachers();
        $result2 = $container->preachers();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test series returns SeriesRepository instance.
     */
    public function testSeriesReturnsSeriesRepository(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(SeriesRepository::class);
        $container->set(SeriesRepository::class, $mockRepository);

        $result = $container->series();

        $this->assertSame($mockRepository, $result);
    }

    /**
     * Test series returns cached instance.
     */
    public function testSeriesReturnsCachedInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(SeriesRepository::class);
        $container->set(SeriesRepository::class, $mockRepository);

        $result1 = $container->series();
        $result2 = $container->series();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test services returns ServiceRepository instance.
     */
    public function testServicesReturnsServiceRepository(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(ServiceRepository::class);
        $container->set(ServiceRepository::class, $mockRepository);

        $result = $container->services();

        $this->assertSame($mockRepository, $result);
    }

    /**
     * Test services returns cached instance.
     */
    public function testServicesReturnsCachedInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(ServiceRepository::class);
        $container->set(ServiceRepository::class, $mockRepository);

        $result1 = $container->services();
        $result2 = $container->services();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test files returns FileRepository instance.
     */
    public function testFilesReturnsFileRepository(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(FileRepository::class);
        $container->set(FileRepository::class, $mockRepository);

        $result = $container->files();

        $this->assertSame($mockRepository, $result);
    }

    /**
     * Test files returns cached instance.
     */
    public function testFilesReturnsCachedInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(FileRepository::class);
        $container->set(FileRepository::class, $mockRepository);

        $result1 = $container->files();
        $result2 = $container->files();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test tags returns TagRepository instance.
     */
    public function testTagsReturnsTagRepository(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(TagRepository::class);
        $container->set(TagRepository::class, $mockRepository);

        $result = $container->tags();

        $this->assertSame($mockRepository, $result);
    }

    /**
     * Test tags returns cached instance.
     */
    public function testTagsReturnsCachedInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(TagRepository::class);
        $container->set(TagRepository::class, $mockRepository);

        $result1 = $container->tags();
        $result2 = $container->tags();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test books returns BookRepository instance.
     */
    public function testBooksReturnsBookRepository(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(BookRepository::class);
        $container->set(BookRepository::class, $mockRepository);

        $result = $container->books();

        $this->assertSame($mockRepository, $result);
    }

    /**
     * Test books returns cached instance.
     */
    public function testBooksReturnsCachedInstance(): void
    {
        $container = Container::getInstance();
        $mockRepository = Mockery::mock(BookRepository::class);
        $container->set(BookRepository::class, $mockRepository);

        $result1 = $container->books();
        $result2 = $container->books();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test multiple repositories can be set independently.
     */
    public function testMultipleRepositoriesCanBeSetIndependently(): void
    {
        $container = Container::getInstance();

        $mockSermons = Mockery::mock(SermonRepository::class);
        $mockPreachers = Mockery::mock(PreacherRepository::class);
        $mockSeries = Mockery::mock(SeriesRepository::class);

        $container->set(SermonRepository::class, $mockSermons);
        $container->set(PreacherRepository::class, $mockPreachers);
        $container->set(SeriesRepository::class, $mockSeries);

        $this->assertSame($mockSermons, $container->sermons());
        $this->assertSame($mockPreachers, $container->preachers());
        $this->assertSame($mockSeries, $container->series());
    }

    /**
     * Test reset clears all cached instances.
     */
    public function testResetClearsAllCachedInstances(): void
    {
        $container1 = Container::getInstance();
        $mockSermons = Mockery::mock(SermonRepository::class);
        $container1->set(SermonRepository::class, $mockSermons);

        // Verify mock is set.
        $this->assertSame($mockSermons, $container1->sermons());

        // Reset and get new container.
        Container::reset();
        $container2 = Container::getInstance();

        // New mock for the new container.
        $newMockSermons = Mockery::mock(SermonRepository::class);
        $container2->set(SermonRepository::class, $newMockSermons);

        // Verify new container has different instance.
        $this->assertNotSame($container1, $container2);
        $this->assertSame($newMockSermons, $container2->sermons());
    }

    /**
     * Test sermons creates real SermonRepository instance via lazy loading.
     */
    public function testSermonsCreatesRealInstanceViaLazyLoading(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result = $container->sermons();

        $this->assertInstanceOf(SermonRepository::class, $result);
    }

    /**
     * Test preachers creates real PreacherRepository instance via lazy loading.
     */
    public function testPreachersCreatesRealInstanceViaLazyLoading(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result = $container->preachers();

        $this->assertInstanceOf(PreacherRepository::class, $result);
    }

    /**
     * Test series creates real SeriesRepository instance via lazy loading.
     */
    public function testSeriesCreatesRealInstanceViaLazyLoading(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result = $container->series();

        $this->assertInstanceOf(SeriesRepository::class, $result);
    }

    /**
     * Test services creates real ServiceRepository instance via lazy loading.
     */
    public function testServicesCreatesRealInstanceViaLazyLoading(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result = $container->services();

        $this->assertInstanceOf(ServiceRepository::class, $result);
    }

    /**
     * Test files creates real FileRepository instance via lazy loading.
     */
    public function testFilesCreatesRealInstanceViaLazyLoading(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result = $container->files();

        $this->assertInstanceOf(FileRepository::class, $result);
    }

    /**
     * Test tags creates real TagRepository instance via lazy loading.
     */
    public function testTagsCreatesRealInstanceViaLazyLoading(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result = $container->tags();

        $this->assertInstanceOf(TagRepository::class, $result);
    }

    /**
     * Test books creates real BookRepository instance via lazy loading.
     */
    public function testBooksCreatesRealInstanceViaLazyLoading(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result = $container->books();

        $this->assertInstanceOf(BookRepository::class, $result);
    }

    /**
     * Test resolve caches instances on repeated calls.
     */
    public function testResolveCachesInstancesOnRepeatedCalls(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $result1 = $container->sermons();
        $result2 = $container->sermons();

        $this->assertSame($result1, $result2);
    }

    /**
     * Test different repository types are cached separately.
     */
    public function testDifferentRepositoryTypesAreCachedSeparately(): void
    {
        $this->mockGlobalWpdb();
        $container = Container::getInstance();

        $sermons = $container->sermons();
        $preachers = $container->preachers();

        $this->assertNotSame($sermons, $preachers);
        $this->assertInstanceOf(SermonRepository::class, $sermons);
        $this->assertInstanceOf(PreacherRepository::class, $preachers);
    }

    /**
     * Mock the global $wpdb for tests that need real repository instantiation.
     */
    private function mockGlobalWpdb(): void
    {
        global $wpdb;
        $mock = Mockery::mock(\wpdb::class);
        $mock->prefix = 'wp_'; // @phpstan-ignore property.notFound
        $wpdb = $mock;
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void
    {
        Container::reset();
        parent::tearDown();
    }
}
