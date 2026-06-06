<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests;

use Psr\Container\ContainerInterface;
use Switon\Core\ContextManagerInterface;
use Switon\Rendering\Frames;
use Switon\Rendering\RendererInterface;
use Switon\Testing\TestCase as BaseTestCase;
use Switon\Viewing\View;
use Switon\Viewing\ViewContext;
use Switon\Viewing\ViewInterface;

/**
 * Base test case for View tests.
 *
 * Provides common functionality for all View tests using Container (as in real applications).
 * No reflection is used - all dependencies are injected through Container's autowiring.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
abstract class TestCase extends BaseTestCase
{
    protected ViewInterface $view;
    protected ContextManagerInterface $contextManager;
    protected RendererInterface $renderer;
    protected ContainerInterface $widgetFactory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        // contextManager needs method configuration, so use mock
        $this->contextManager = $this->createMock(ContextManagerInterface::class);
        // renderer and widgetFactory are used with expects() in some tests (ViewWidgetTest),
        // but also used without expectations in others. Use mocks and allow without expectations at class level.
        $this->renderer = $this->createMock(RendererInterface::class);
        $this->widgetFactory = $this->createMock(ContainerInterface::class);

        // Register dependencies in container (replace default ContextManager with mock)
        $this->container->set(ContextManagerInterface::class, $this->contextManager);
        $this->container->set(RendererInterface::class, $this->renderer);
        $this->container->set(ContainerInterface::class, $this->widgetFactory);

        // Setup context manager to return a new context
        $this->contextManager->method('getContext')
            ->willReturn(new ViewContext());

        // Create View instance using container (realistic way, no reflection needed)
        // Container automatically handles #[Autowired] property injection
        $this->view = $this->container->make(View::class);
    }

    protected function frames(string $content, array $vars = []): Frames
    {
        return Frames::of($vars)->setContent($content);
    }
}
