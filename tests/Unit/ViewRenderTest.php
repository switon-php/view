<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Viewing\Tests\TestCase;

/**
 * Test cases for View rendering operations.
 *
 * Tests render() method with various scenarios: direct template paths,
 * Controller::Action format, layout rendering, URL fixing.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewRenderTest extends TestCase
{
    /**
     * Test rendering a direct template path.
     *
     * Verifies that render() can render a template using a direct path.
     */
    public function testRenderDirectTemplate(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $template = '@view/Test/Template';
        $vars = ['title' => 'Test Title'];
        $renderedContent = '<div>Rendered Content</div>';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($tpl, $v) use ($template, $vars, $renderedContent) {
                if ($tpl === $template) {
                    return $this->frames($renderedContent);
                }
                return $this->frames($renderedContent);
            });

        // Act
        $result = $this->view->render($template, $vars);

        // Assert
        $this->assertSame($renderedContent, $result, 'render() should return rendered content');
    }

    /**
     * Test rendering with layout.
     *
     * Verifies that render() applies layout when layout is set.
     */
    public function testRenderWithLayout(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $template = '@view/Test/Template';
        $layout = '@view/Layouts/Custom';
        $templateContent = '<div>Template Content</div>';
        $layoutContent = '<html><body>' . $templateContent . '</body></html>';

        $this->view->setLayout($layout);
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($tpl, $vars) use ($template, $layout, $templateContent, $layoutContent) {
                if ($tpl === $template) {
                    return $this->frames($templateContent);
                }
                if ($tpl === $layout) {
                    return $this->frames($layoutContent);
                }
                return $this->frames('');
            });

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame($layoutContent, $result, 'render() should apply layout');
    }

    /**
     * Test rendering without layout when layout is disabled.
     *
     * Verifies that render() skips layout when layout is disabled.
     */
    public function testRenderWithoutLayout(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $template = '@view/Test/Template';
        $templateContent = '<div>Template Content</div>';

        $this->view->disableLayout();
        $this->renderer->expects($this->once())
            ->method('render')
            ->with($template, $this->anything())
            ->willReturn($this->frames($templateContent));

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame($templateContent, $result, 'render() should not apply layout when disabled');
    }

    /**
     * Test rendering with layout set to null uses default layout.
     *
     * Verifies that when layout is null, the default layout is used.
     */
    public function testRenderWithNullLayoutUsesDefault(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $template = '@view/Test/Template';
        $defaultLayout = '@view/Layouts/Default';
        $templateContent = '<div>Template Content</div>';
        $layoutContent = '<html><body>' . $templateContent . '</body></html>';

        $context = $this->view->getContext();
        $context->layout = null;
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($tpl, $vars) use ($template, $defaultLayout, $templateContent, $layoutContent) {
                if ($tpl === $template) {
                    return $this->frames($templateContent);
                }
                if ($tpl === $defaultLayout) {
                    return $this->frames($layoutContent);
                }
                return $this->frames('');
            });

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame($layoutContent, $result, 'render() should use default layout when layout is null');
    }

    /**
     * Test rendering merges provided variables with context variables.
     *
     * Verifies that variables passed to render() are merged with existing context variables.
     */
    public function testRenderMergesVariables(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $template = '@view/Test/Template';
        $this->view->setVar('existing', 'Existing Value');
        $renderVars = ['new' => 'New Value'];
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturnCallback(function ($tpl, $v) use ($template, $renderVars) {
                if ($tpl === $template) {
                    return $this->frames('<div>Content</div>');
                }
                return $this->frames('<div>Content</div>');
            });

        // Act
        $this->view->render($template, $renderVars);

        // Assert - Variables passed to render() should replace context variables
        $this->assertSame('New Value', $this->view->getVar('new'), 'New variable should be set');
    }

    /**
     * Test rendering with URL prefix fixes URLs.
     *
     * Verifies that render() fixes URLs in content when fixUrl is enabled and prefix is provided.
     */
    public function testRenderFixesUrlsWithPrefix(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $template = '@view/Test/Template';
        $prefix = '/app';
        $contentWithUrls = '<a href="/page">Link</a><img src="/image.jpg">';
        $expectedContent = '<a href="/app/page">Link</a><img src="/app/image.jpg">';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturn($this->frames($contentWithUrls));

        // Act
        $result = $this->view->render($template, [], $prefix);

        // Assert
        $this->assertSame($expectedContent, $result, 'render() should fix URLs with prefix');
    }

    /**
     * Test rendering without URL prefix does not fix URLs.
     *
     * Verifies that render() does not modify URLs when prefix is empty.
     */
    public function testRenderDoesNotFixUrlsWithoutPrefix(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $template = '@view/Test/Template';
        $contentWithUrls = '<a href="/page">Link</a>';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturn($this->frames($contentWithUrls));

        // Act
        $result = $this->view->render($template, [], '');

        // Assert
        $this->assertSame($contentWithUrls, $result, 'render() should not modify URLs without prefix');
    }

    /**
     * Test fixUrl method.
     *
     * Verifies that fixUrl() correctly prefixes URLs in content.
     */
    public function testFixUrl(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $prefix = '/app';
        $content = '<a href="/page">Link</a><img src="/image.jpg" data-src="/thumb.jpg">';
        $expectedContent = '<a href="/app/page">Link</a><img src="/app/image.jpg" data-src="/app/thumb.jpg">';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($prefix);

        // Assert
        $this->assertSame($expectedContent, $this->view->getContent(), 'fixUrl() should prefix URLs');
    }

    /**
     * Test fixUrl with empty prefix does nothing.
     *
     * Verifies that fixUrl() does not modify content when prefix is empty.
     */
    public function testFixUrlWithEmptyPrefix(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $content = '<a href="/page">Link</a>';
        $this->view->setContent($content);

        // Act
        $this->view->fixUrl('');

        // Assert
        $this->assertSame($content, $this->view->getContent(), 'fixUrl() should not modify content with empty prefix');
    }

    public function testFixUrlSkipsProtocolRelativeUrls(): void
    {
        // Arrange
        $content = '<script src="//cdn.example.com/app.js"></script><a href="/local">Local</a>';
        $this->view->setContent($content);

        // Act
        $this->view->fixUrl('/app');

        // Assert
        $this->assertSame(
            '<script src="//cdn.example.com/app.js"></script><a href="/app/local">Local</a>',
            $this->view->getContent()
        );
    }

    /**
     * Test block method renders block template.
     *
     * Verifies that block() renders a block template with provided variables.
     */
    public function testBlock(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $path = 'Header';
        $expectedPath = '@view/Blocks/Header';
        $vars = ['title' => 'Header Title'];

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($expectedPath, $vars)
            ->willReturnCallback(function ($path, $vars) {
                return $this->frames('<header>Header Content</header>');
            });

        // Act
        ob_start();
        $this->view->block($path, $vars);
        $output = ob_get_clean();

        // Assert
        $this->assertSame('<header>Header Content</header>', $output, 'block() should render block template');
    }

    /**
     * Test block with full path does not modify path.
     *
     * Verifies that block() does not modify paths that start with @ or contain /.
     */
    public function testBlockWithFullPath(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $path = '@view/Custom/Block';
        $vars = ['title' => 'Title'];

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($path, $vars)
            ->willReturn(\Switon\Rendering\Frames::of()->setContent('<div>Block Content</div>'));

        // Act
        ob_start();
        $this->view->block($path, $vars);
        ob_get_clean();

        // Assert - Test passes if render() is called with correct path
        $this->assertTrue(true);
    }
}
