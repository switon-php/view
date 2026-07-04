<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Core\ContainerInterface;
use Switon\Core\PathAliasInterface;
use Switon\Eventing\ListenerProviderInterface;
use Switon\Testing\Container;
use Switon\Viewing\ServiceProvider;
use PHPUnit\Framework\TestCase;
use Switon\Viewing\ViewRenderer;

/**
 * Test cases for ServiceProvider operations.
 *
 * Tests service provider registration and boot methods.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ServiceProviderTest extends TestCase
{
    protected ServiceProvider $serviceProvider;
    protected ContainerInterface $containerStub;
    protected ListenerProviderInterface $listenerProvider;
    protected PathAliasInterface $pathAlias;
    protected Container $testContainer;

    protected function setUp(): void
    {

        $this->testContainer = new Container();

        // Container is only passed as parameter, no method calls to verify, use stub
        $this->containerStub = $this->createStub(ContainerInterface::class);

        // Create mocks for dependencies
        $this->listenerProvider = $this->createMock(ListenerProviderInterface::class);
        $this->pathAlias = $this->createMock(PathAliasInterface::class);

        // Register mocks in container
        $this->testContainer->replace(ListenerProviderInterface::class, $this->listenerProvider);
        $this->testContainer->replace(PathAliasInterface::class, $this->pathAlias);

        // Create ServiceProvider through container - autowiring will inject the mocks
        $this->serviceProvider = $this->testContainer->make(ServiceProvider::class);
    }

    /**
     * Test register method does nothing.
     *
     * Verifies that register() method completes without errors.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testRegister(): void
    {
        // Act
        $this->serviceProvider->register($this->containerStub);

        // Assert - Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test boot method registers ViewRenderer.
     *
     * Verifies that boot() registers ViewRenderer as event listener.
     */
    public function testBoot(): void
    {
        // Arrange
        $this->listenerProvider->expects($this->once())
            ->method('register')
            ->with(ViewRenderer::class);

        $this->pathAlias->expects($this->once())
            ->method('has')
            ->with('@view')
            ->willReturn(false);
        $this->pathAlias->expects($this->once())
            ->method('set')
            ->with('@view', '@app/View');

        // Act
        $this->serviceProvider->boot();

        // Assert - Test passes if register() is called with ViewRenderer::class
        $this->assertTrue(true);
    }
}
