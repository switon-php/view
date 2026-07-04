<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionMethod;
use Switon\Core\ArrayableInterface;
use Switon\Di\Container as DiContainer;
use Switon\Di\ServiceProvider as DiServiceProvider;
use Switon\Http\Event\RequestRendering;
use Switon\Http\RequestInterface;
use Switon\Http\RequestHandlerInterface;
use Switon\Http\ResponseInterface;
use Switon\Viewing\Event\VarsResolving;
use Switon\Viewing\ViewInterface;
use Switon\Viewing\ViewRenderer;
use stdClass;

/**
 * Test cases for ViewRenderer - the critical bridge between HTTP and View.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewRendererTest extends BaseTestCase
{
    protected ViewRenderer $viewRenderer;
    protected ViewInterface $view;
    protected EventDispatcherInterface $eventDispatcher;
    protected RequestHandlerInterface $requestHandler;
    protected DiContainer $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new DiContainer();
        $diProvider = new DiServiceProvider();
        $diProvider->register($this->container);
        $diProvider->boot();

        // Create mocks for dependencies
        $this->view = $this->createMock(ViewInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->requestHandler = $this->createStub(RequestHandlerInterface::class);

        // Register mocks in container
        $this->container->set(ViewInterface::class, $this->view);
        $this->container->set(EventDispatcherInterface::class, $this->eventDispatcher);
        $this->container->set(RequestHandlerInterface::class, $this->requestHandler);

        // Create ViewRenderer through container - autowiring will inject the mocks
        $this->viewRenderer = $this->container->make(ViewRenderer::class);
    }

    /**
     * Test skipping when response has content - critical optimization.
     */
    public function testOnRenderingSkipsWhenResponseHasContent(): void
    {
        $method = $this->createStub(ReflectionMethod::class);
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createStub(RequestInterface::class);

        $response->expects($this->once())->method('hasContent')->willReturn(true);

        // RequestRendering($method, $return, $request, $response, $prefix)
        $event = new RequestRendering($this->requestHandler, $method, null, $request, $response, '');

        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->view->expects($this->never())->method('setVars');
        $this->view->expects($this->never())->method('render');

        $this->viewRenderer->onRendering($event);

        $this->assertTrue(true);
    }

    /**
     * Test array return value handling.
     */
    public function testOnRenderingHandlesArrayReturn(): void
    {
        $method = $this->createStub(ReflectionMethod::class);
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $returnValue = ['title' => 'Test', 'content' => 'Content'];

        $response->method('hasContent')->willReturn(false);
        $request->method('handler')->willReturn('Test::action');

        $event = new RequestRendering($this->requestHandler, $method, $returnValue, $request, $response, '/app');

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($e) {
                if ($e instanceof VarsResolving) {
                    $e->vars = ['global' => 'Global'];
                }
                return $e;
            });

        $this->view->expects($this->once())->method('setVars');
        $this->view->expects($this->once())->method('render')->willReturn('<html>content</html>');
        $response->expects($this->once())->method('setContent');
        $response->expects($this->once())->method('setHeader');

        $this->viewRenderer->onRendering($event);

        $this->assertTrue(true);
    }

    /**
     * Test object with toArray() method.
     */
    public function testOnRenderingHandlesObjectWithToArray(): void
    {
        $method = $this->createStub(ReflectionMethod::class);
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $returnObject = new class () implements ArrayableInterface {
            public function toArray(): array
            {
                return ['data' => 'Object Data'];
            }
        };

        $response->method('hasContent')->willReturn(false);
        $request->method('handler')->willReturn('Test::action');

        $event = new RequestRendering($this->requestHandler, $method, $returnObject, $request, $response, '');

        $this->eventDispatcher->method('dispatch')->willReturnArgument(0);
        $this->view->method('render')->willReturn('<html>content</html>');

        $this->viewRenderer->onRendering($event);

        $this->assertTrue(true);
    }

    /**
     * Test plain object handling.
     */
    public function testOnRenderingHandlesPlainObject(): void
    {
        $method = $this->createStub(ReflectionMethod::class);
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $returnObject = new stdClass();
        $returnObject->property = 'value';

        $response->method('hasContent')->willReturn(false);
        $request->method('handler')->willReturn('Test::action');

        $event = new RequestRendering($this->requestHandler, $method, $returnObject, $request, $response, '');

        $this->eventDispatcher->method('dispatch')->willReturnArgument(0);
        $this->view->method('render')->willReturn('<html>content</html>');

        $this->viewRenderer->onRendering($event);

        $this->assertTrue(true);
    }

    /**
     * Test null return value.
     */
    public function testOnRenderingHandlesNullReturn(): void
    {
        $method = $this->createStub(ReflectionMethod::class);
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(RequestInterface::class);

        $response->method('hasContent')->willReturn(false);
        $request->method('handler')->willReturn('Test::action');

        $event = new RequestRendering($this->requestHandler, $method, null, $request, $response, '');

        $this->eventDispatcher->method('dispatch')->willReturnArgument(0);
        $this->view->method('render')->willReturn('<html>content</html>');

        $this->viewRenderer->onRendering($event);

        $this->assertTrue(true);
    }
}
