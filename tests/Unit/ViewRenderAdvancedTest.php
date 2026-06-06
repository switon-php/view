<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Rendering\Frames;
use Switon\Viewing\Tests\TestCase;

/**
 * Test cases for View advanced rendering operations.
 *
 * Tests advanced render() scenarios: Controller::Action format, URL rewrite disabled,
 * empty vars array, and other edge cases.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewRenderAdvancedTest extends TestCase
{
    protected function vars(array|Frames $vars): array
    {
        return $vars instanceof Frames ? [...$vars->all(), 'content' => $vars->content()] : $vars;
    }

    /**
     * Test rendering with Controller::Action format.
     *
     * Verifies that render() correctly resolves Controller::Action format to template path.
     */
    public function testRenderWithControllerActionFormat(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange - Use a real controller class for testing
        $template = __CLASS__ . '::testAction';
        $renderedContent = '<div>Rendered Content</div>';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($tpl, $vars) use ($renderedContent) {
                return $this->frames($renderedContent);
            });

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame($renderedContent, $result, 'render() should handle Controller::Action format');
    }

    /**
     * Test rendering with empty vars array does not overwrite context vars.
     *
     * Verifies that render() with empty vars array preserves existing context variables.
     */
    public function testRenderWithEmptyVarsArray(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $template = '@view/Test/Template';
        $this->view->setVar('existing', 'Existing Value');
        $renderedContent = '<div>Rendered Content</div>';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($tpl, $vars) use ($renderedContent) {
                $vars = $this->vars($vars);
                if ($tpl !== '@view/Test/Template') {
                    return $this->frames($renderedContent);
                }
                // Verify that existing vars are preserved
                $this->assertArrayHasKey('existing', $vars);
                return $this->frames($renderedContent);
            });

        // Act - Pass empty array
        $result = $this->view->render($template, []);

        // Assert
        $this->assertSame($renderedContent, $result, 'render() should preserve context vars when empty array is passed');
        $this->assertSame('Existing Value', $this->view->getVar('existing'), 'Existing variable should be preserved');
    }

    /**
     * Test rendering with URL rewrite disabled.
     *
     * Verifies that render() does not fix URLs when rewrite is false.
     */
    public function testRenderWithAutofixUrlDisabled(): void
    {
        // Create View with rewrite disabled using make()
        $contextManager = $this->createMock(\Switon\Core\ContextManagerInterface::class);
        $renderer = $this->createMock(\Switon\Rendering\RendererInterface::class);
        $widgetFactory = $this->createStub(\Psr\Container\ContainerInterface::class);
        $contextManager->method('getContext')->willReturn(new \Switon\Viewing\ViewContext());

        // Remove existing services before re-setting (they may have been resolved in setUp)
        $this->container->remove(\Switon\Core\ContextManagerInterface::class);
        $this->container->remove(\Switon\Rendering\RendererInterface::class);
        $this->container->remove(\Psr\Container\ContainerInterface::class);

        $this->container->set(\Switon\Core\ContextManagerInterface::class, $contextManager);
        $this->container->set(\Switon\Rendering\RendererInterface::class, $renderer);
        $this->container->set(\Psr\Container\ContainerInterface::class, $widgetFactory);

        $view = $this->make(\Switon\Viewing\View::class, [
            'rewrite' => false,
        ]);

        // Arrange
        $template = '@view/Test/Template';
        $prefix = '/app';
        $contentWithUrls = '<a href="/page">Link</a>';
        $renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturn($this->frames($contentWithUrls));

        // Act
        $result = $view->render($template, [], $prefix);

        // Assert - URLs should not be fixed when rewrite is false
        $this->assertSame($contentWithUrls, $result, 'render() should not fix URLs when rewrite is disabled');
    }

    /**
     * Test fixUrl with different URL formats.
     *
     * Verifies that fixUrl() correctly handles various URL formats.
     */
    public function testFixUrlWithDifferentFormats(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $prefix = '/app';
        $content = '<form action="/submit"><a href="/page">Link</a><img src="/image.jpg" data-src="/thumb.jpg"></form>';
        $expectedContent = '<form action="/app/submit"><a href="/app/page">Link</a><img src="/app/image.jpg" data-src="/app/thumb.jpg"></form>';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($prefix);

        // Assert
        $this->assertSame($expectedContent, $this->view->getContent(), 'fixUrl() should handle all URL attributes');
    }

    /**
     * Test fixUrl does not modify absolute URLs.
     *
     * Verifies that fixUrl() does not modify URLs starting with // or http://.
     */
    public function testFixUrlDoesNotModifyAbsoluteUrls(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $prefix = '/app';
        $content = '<a href="//example.com/page">Absolute</a><img src="http://example.com/image.jpg">';
        $expectedContent = $content; // Should not be modified

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($prefix);

        // Assert
        $this->assertSame($expectedContent, $this->view->getContent(), 'fixUrl() should not modify absolute URLs');
    }

    /**
     * Test fixUrl with different quote styles.
     *
     * Verifies that fixUrl() handles single quotes, double quotes, and backticks.
     */
    public function testFixUrlWithDifferentQuoteStyles(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $prefix = '/app';
        $content = '<a href=\'/page\'>Single</a><img src="/image.jpg"><a href=`/link`>Backtick</a>';
        $expectedContent = '<a href=\'/app/page\'>Single</a><img src="/app/image.jpg"><a href=`/app/link`>Backtick</a>';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($prefix);

        // Assert
        $this->assertSame($expectedContent, $this->view->getContent(), 'fixUrl() should handle different quote styles');
    }
}
