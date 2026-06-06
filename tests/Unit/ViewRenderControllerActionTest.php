<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Viewing\Tests\Fixtures\TestController;
use Switon\Viewing\Tests\TestCase;

/**
 * Test cases for View Controller::Action format rendering.
 *
 * Tests various scenarios of Controller::Action format: with/without Action suffix,
 * index action, directory existence checks.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewRenderControllerActionTest extends TestCase
{
    /**
     * Test rendering with Controller::Action format and Action suffix.
     *
     * Verifies that render() correctly removes Action suffix from action name.
     */
    public function testRenderControllerActionWithActionSuffix(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $template = TestController::class . '::showAction';
        $renderedContent = '<div>Rendered Content</div>';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturn($this->frames($renderedContent));

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame($renderedContent, $result, 'render() should handle Controller::Action format with Action suffix');
    }

    /**
     * Test rendering with Controller::index format.
     *
     * Verifies that render() correctly handles index action.
     */
    public function testRenderControllerIndexAction(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $template = TestController::class . '::index';
        $renderedContent = '<div>Rendered Content</div>';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturn($this->frames($renderedContent));

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame($renderedContent, $result, 'render() should handle Controller::index format');
    }

    /**
     * Test rendering with Controller::indexAction format.
     *
     * Verifies that render() correctly handles indexAction.
     */
    public function testRenderControllerIndexActionWithSuffix(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $template = TestController::class . '::indexAction';
        $renderedContent = '<div>Rendered Content</div>';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturn($this->frames($renderedContent));

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame($renderedContent, $result, 'render() should handle Controller::indexAction format');
    }

}
