<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Switon\Core\ContextManagerInterface;
use Switon\Di\Container as DiContainer;
use Switon\Di\ServiceProvider as DiServiceProvider;
use Switon\Rendering\Frames;
use Switon\Rendering\RendererInterface;
use Switon\Viewing\View;
use Switon\Viewing\ViewContext;
use Switon\Viewing\ViewInterface;

/**
 * Base test case for View tests.
 *
 * Builds a minimal DI container with mocked view dependencies.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
abstract class TestCase extends BaseTestCase
{
    protected DiContainer $container;
    protected ViewInterface $view;
    protected ContextManagerInterface $contextManager;
    protected RendererInterface $renderer;
    protected ContainerInterface $widgetFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new DiContainer();
        $diProvider = new DiServiceProvider();
        $diProvider->register($this->container);
        $diProvider->boot();

        $this->contextManager = $this->createMock(ContextManagerInterface::class);
        $this->renderer = $this->createMock(RendererInterface::class);
        $this->widgetFactory = $this->createMock(ContainerInterface::class);

        $this->container->set(ContextManagerInterface::class, $this->contextManager);
        $this->container->set(RendererInterface::class, $this->renderer);
        $this->container->set(ContainerInterface::class, $this->widgetFactory);

        $this->contextManager->method('getContext')
            ->willReturn(new ViewContext());

        $this->view = $this->container->make(View::class);
    }

    protected function frames(string $content, array $vars = []): Frames
    {
        return Frames::of($vars)->setContent($content);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $name
     *
     * @phpstan-return T
     */
    protected function make(string $name, array $parameters = []): mixed
    {
        return $this->container->make($name, $parameters);
    }
}
