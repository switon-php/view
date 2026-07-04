<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Switon\Core\ContextAware;
use Switon\Core\ContextManagerInterface;
use Switon\Di\Container as DiContainer;
use Switon\Di\ServiceProvider as DiServiceProvider;
use Switon\Rendering\Frames;
use Switon\Rendering\RendererInterface;
use Switon\Viewing\Tests\Fixtures\TestWidget;
use Switon\Viewing\View;
use Switon\Viewing\ViewContext;
use Switon\Viewing\ViewInterface;
use RuntimeException;
use stdClass;

class InMemoryContextManager implements ContextManagerInterface
{
    /** @var array<string, ViewContext> */
    protected array $contexts = [];

    public function getContext(ContextAware $object, int $cid = 0): mixed
    {
        $key = spl_object_hash($object);

        return $this->contexts[$key] ??= new ViewContext();
    }
}

/**
 * Integration tests for View component.
 *
 * Tests real component interactions without mocks where possible.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewIntegrationTest extends TestCase
{
    protected DiContainer $container;
    protected ViewInterface $view;
    protected RendererInterface $renderer;
    protected ContainerInterface $widgetFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new DiContainer();
        $diProvider = new DiServiceProvider();
        $diProvider->register($this->container);
        $diProvider->boot();

        // Keep this integration test package-local while preserving per-instance state behavior.
        $contextManager = new InMemoryContextManager();
        $this->container->set(ContextManagerInterface::class, $contextManager);

        // Mock only external dependencies
        $this->renderer = $this->createMock(RendererInterface::class);
        $this->widgetFactory = $this->createMock(ContainerInterface::class);

        $this->container->set(RendererInterface::class, $this->renderer);
        $this->container->set(ContainerInterface::class, $this->widgetFactory);

        // Create View through container
        $this->view = $this->container->make(View::class);
    }

    protected function frames(string $content, array $vars = []): Frames
    {
        return Frames::of($vars)->setContent($content);
    }

    protected function vars(array|Frames $vars): array
    {
        if ($vars instanceof Frames) {
            return [...$vars->all(), 'content' => $vars->content()];
        }

        return $vars;
    }

    /**
     * Test complete view workflow with real context management.
     */
    public function testCompleteViewWorkflow(): void
    {
        // Arrange - Setup renderer expectations
        $this->renderer->method('render')
            ->willReturnCallback(function ($template, $vars) {
                $vars = $this->vars($vars);
                if (str_contains($template, 'Layout')) {
                    return $this->frames('<html><body>' . ($vars['content'] ?? 'Content') . '</body></html>', $vars);
                }
                return $this->frames('<div>Template: ' . $template . '</div>', $vars);
            });

        // Act - Complete workflow
        $this->view
            ->setVar('title', 'Integration Test')
            ->setVar('description', 'Testing real component interaction')
            ->setLayout('TestLayout');

        $result = $this->view->render('@view/Test/Template', [
            'additional' => 'Additional Data'
        ]);

        // Assert - Verify complete workflow
        $this->assertStringContainsString('<html><body>', $result);
        $this->assertStringContainsString('</body></html>', $result);

        // Verify context state
        $context = $this->view->getContext();
        $this->assertInstanceOf(ViewContext::class, $context);
        $this->assertSame('Integration Test', $context->vars['title']);
        $this->assertSame('Additional Data', $context->vars['additional']);
    }

    /**
     * Test context persistence across multiple operations.
     */
    public function testContextPersistence(): void
    {
        // Act - Multiple operations
        $this->view->setVar('persistent', 'Value1');
        $context1 = $this->view->getContext();

        $this->view->setVar('another', 'Value2');
        $context2 = $this->view->getContext();

        // Assert - Same context instance
        $this->assertSame($context1, $context2);
        $this->assertSame('Value1', $context2->vars['persistent']);
        $this->assertSame('Value2', $context2->vars['another']);
    }

    /**
     * Test URL fixing integration.
     */
    public function testUrlFixingIntegration(): void
    {
        // Arrange
        $this->renderer->method('render')
            ->willReturn(\Switon\Rendering\Frames::of()->setContent('<a href="/page">Link</a><img src="/image.jpg">'));

        // Act
        $result = $this->view->render('@view/Test', [], '/app');

        // Assert
        $this->assertStringContainsString('href="/app/page"', $result);
        $this->assertStringContainsString('src="/app/image.jpg"', $result);
    }

    /**
     * Test variable merging behavior in real scenario.
     */
    public function testVariableMergingBehavior(): void
    {
        // Arrange
        $this->renderer->method('render')
            ->willReturnCallback(function ($template, $vars) {
                $vars = $this->vars($vars);
                if ($template !== '@view/Test') {
                    return $this->frames('<div>Content</div>', $vars);
                }
                // Verify all variables are present
                $this->assertArrayHasKey('context', $vars);
                $this->assertArrayHasKey('render', $vars);
                $this->assertArrayHasKey('overwrite', $vars);
                $this->assertSame('Context Value', $vars['context']);
                $this->assertSame('Render Value', $vars['render']);
                $this->assertSame('Render Overwrite', $vars['overwrite']); // Render vars should win
                return $this->frames('<div>Content</div>', $vars);
            });

        // Act
        $this->view->setVar('context', 'Context Value');
        $this->view->setVar('overwrite', 'Context Overwrite');

        $this->view->render('@view/Test', [
            'render' => 'Render Value',
            'overwrite' => 'Render Overwrite'
        ]);

        // Assert - Test passes if renderer callback assertions pass
        $this->assertTrue(true);
    }

    /**
     * Test widget rendering end-to-end.
     */
    public function testWidgetRenderingEndToEnd(): void
    {
        // Arrange
        $widget = new TestWidget();
        $this->widgetFactory->expects($this->once())
            ->method('get')
            ->with(TestWidget::class)
            ->willReturn($widget);
        $this->renderer->method('render')
            ->willReturnCallback(function ($view, $vars) {
                $content = '<div>Widget Content</div>';
                return \Switon\Rendering\Frames::of()->setContent($content);
            });

        // Act
        ob_start();
        $this->view->widget(TestWidget::class, ['title' => 'Test Widget']);
        $output = ob_get_clean();

        // Assert
        $this->assertStringContainsString('Widget Content', $output);
    }

    /**
     * Test error recovery when renderer throws.
     */
    public function testErrorRecoveryWhenRendererThrows(): void
    {
        // Arrange
        $this->renderer->method('render')->willThrowException(new RuntimeException('Render failed'));

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Render failed');

        $this->view->render('@view/Test');
    }

    /**
     * Test layout inheritance across multiple renders.
     */
    public function testLayoutInheritanceAcrossRenders(): void
    {
        // Arrange
        $this->renderer->method('render')
            ->willReturnCallback(function ($template, $vars, $frames = null) {
                $vars = $frames instanceof Frames ? $this->vars($frames) : $this->vars($vars);
                if ($template === 'MainLayout') {
                    return $this->frames('<html><head>' . ($vars['head'] ?? '') . '</head><body>' . ($vars['body'] ?? '') . '</body></html>', $vars);
                }
                return $this->frames('<div>' . ($vars['body'] ?? $vars['content'] ?? 'Content') . '</div>', $vars);
            });

        // Act - First render with layout
        $this->view->setLayout('MainLayout');
        $this->view->setVar('head', '<title>Page 1</title>');
        $result1 = $this->view->render('@view/Page1', ['body' => '<h1>Page 1</h1>']);

        // Act - Second render with same layout
        $this->view->setVar('head', '<title>Page 2</title>');
        $result2 = $this->view->render('@view/Page2', ['body' => '<h1>Page 2</h1>']);

        // Assert
        $this->assertStringContainsString('<title>Page 1</title>', $result1);
        $this->assertStringContainsString('<h1>Page 1</h1>', $result1);
        $this->assertStringContainsString('<title>Page 2</title>', $result2);
        $this->assertStringContainsString('<h1>Page 2</h1>', $result2);
    }

    /**
     * Test complex variable types handling.
     */
    public function testComplexVariableTypes(): void
    {
        // Arrange
        $complexObject = new stdClass();
        $complexObject->nested = ['deep' => 'value'];
        $this->renderer->method('render')
            ->willReturnCallback(function ($template, $vars) use ($complexObject) {
                $vars = $this->vars($vars);
                if ($template !== '@view/Test') {
                    return $this->frames('<div>Rendered</div>', $vars);
                }
                $this->assertSame($complexObject, $vars['object']);
                $this->assertSame(['key' => 'value'], $vars['array']);
                $this->assertNull($vars['null']);
                $this->assertSame(42, $vars['int']);
                $this->assertSame(3.14, $vars['float']);
                $this->assertTrue($vars['bool']);
                return $this->frames('<div>Rendered</div>', $vars);
            });

        // Act
        $this->view->setVars([
            'object' => $complexObject,
            'array' => ['key' => 'value'],
            'null' => null,
            'int' => 42,
            'float' => 3.14,
            'bool' => true
        ]);

        $this->view->render('@view/Test');

        // Assert - Verified via callback
        $this->assertTrue(true);
    }
}
