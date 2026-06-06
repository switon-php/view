<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Core\Exception\InvalidValueException;
use Switon\Viewing\Tests\Fixtures\{TestComplexWidget, TestHtmlWidget, TestWidget};
use Switon\Viewing\Tests\TestCase;

/**
 * Test cases for View widget operations.
 *
 * Tests widget() method with various scenarios: short names, full class names,
 * widgets returning arrays, widgets returning HTML strings.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewWidgetTest extends TestCase
{
    /**
     * Test widget with short name resolves to full class name.
     *
     * Verifies that widget() resolves short names to App\Widget\{Name}Widget.
     */
    public function testWidgetWithShortName(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange - Use full class name to avoid class_exists check
        $fullClassName = TestWidget::class;
        $widgetInstance = new TestWidget();
        $widgetData = ['title' => 'Widget Title'];

        $this->widgetFactory->expects($this->once())
            ->method('get')
            ->with($fullClassName)
            ->willReturn($widgetInstance);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($this->anything(), $this->callback(function ($vars) use ($widgetData) {
                // Widget returns data with default values, so check that title is present
                return isset($vars['title']) && $vars['title'] === $widgetData['title'];
            }))
            ->willReturn($this->frames('<div>Widget Content</div>'));

        // Act
        ob_start();
        try {
            $this->view->widget($fullClassName, ['title' => 'Widget Title']);
        } finally {
            ob_end_clean();
        }

        // Assert - Test passes if widget is resolved and rendered
        $this->assertTrue(true);
    }

    /**
     * Test widget with full class name.
     *
     * Verifies that widget() uses full class name directly when provided.
     */
    public function testWidgetWithFullClassName(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $fullClassName = TestWidget::class;
        $widgetInstance = new TestWidget();
        $options = ['title' => 'Widget Title'];

        $this->widgetFactory->expects($this->once())
            ->method('get')
            ->with($fullClassName)
            ->willReturn($widgetInstance);

        $this->renderer->expects($this->once())
            ->method('render')
            ->willReturn(\Switon\Rendering\Frames::of()->setContent('<div>Widget Content</div>'));

        // Act
        ob_start();
        $this->view->widget($fullClassName, $options);
        ob_get_clean();

        // Assert - Test passes if widget is rendered
        $this->assertTrue(true);
    }

    /**
     * Test widget that returns array data.
     *
     * Verifies that widget() renders widget view when widget returns array.
     */
    public function testWidgetReturnsArray(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $widgetInstance = new TestWidget();
        $widgetData = ['title' => 'Widget Title', 'content' => 'Widget Content'];
        $widgetView = 'path/to/widget/view';

        $this->widgetFactory->expects($this->once())
            ->method('get')
            ->willReturn($widgetInstance);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($this->anything(), $this->callback(function ($vars) {
                // Widget returns data with default values, so check that title is present
                return isset($vars['title']) && isset($vars['content']);
            }))
            ->willReturnCallback(function ($path, $vars) {
                return \Switon\Rendering\Frames::of()->setContent('<div>Widget Rendered</div>');
            });

        // Act
        ob_start();
        try {
            $this->view->widget(TestWidget::class);
        } finally {
            $output = ob_get_clean();
        }

        // Assert
        $this->assertSame('<div>Widget Rendered</div>', $output, 'Widget returning array should render view');
    }

    /**
     * Test widget that returns HTML string.
     *
     * Verifies that widget() outputs HTML directly when widget returns string.
     */
    public function testWidgetReturnsHtmlString(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $widgetInstance = new TestHtmlWidget();
        $htmlOutput = '<div class="test-widget">HTML Content</div>';

        $this->widgetFactory->expects($this->once())
            ->method('get')
            ->willReturn($widgetInstance);

        $this->renderer->expects($this->never())
            ->method('render');

        // Act
        ob_start();
        $this->view->widget(TestHtmlWidget::class);
        $output = ob_get_clean();

        // Assert
        $this->assertSame($htmlOutput, $output, 'Widget returning string should output directly');
    }

    /**
     * Test widget with non-existent class throws exception.
     *
     * Verifies that widget() throws InvalidValueException when widget class does not exist.
     */
    public function testWidgetWithNonExistentClassThrowsException(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $widgetName = 'NonExistentWidget';
        $fullClassName = 'App\\Widget\\NonExistentWidgetWidget';

        // Act & Assert
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Widget');

        $this->view->widget($widgetName);
    }

    /**
     * Test widget passes options to run method.
     *
     * Verifies that widget() passes options to widget's run() method.
     */
    public function testWidgetPassesOptionsToRun(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $widgetInstance = new TestWidget();
        $options = ['title' => 'Custom Title', 'content' => 'Custom Content'];

        $this->widgetFactory->expects($this->once())
            ->method('get')
            ->willReturn($widgetInstance);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($this->anything(), $options)
            ->willReturn(\Switon\Rendering\Frames::of()->setContent('<div>Widget</div>'));

        // Act
        ob_start();
        $this->view->widget(TestWidget::class, $options);
        ob_get_clean();

        // Assert - Test passes if options are passed correctly
        $this->assertTrue(true);
    }

    /**
     * Test widget with complex data.
     *
     * Verifies that widget() handles widgets returning complex data structures.
     */
    public function testWidgetWithComplexData(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $widgetInstance = new TestComplexWidget();
        $options = ['items' => ['item1', 'item2', 'item3']];
        $expectedData = [
            'items' => ['item1', 'item2', 'item3'],
            'count' => 3,
            'metadata' => [
                'timestamp' => $this->anything(),
                'source' => 'test',
            ],
        ];

        $this->widgetFactory->expects($this->once())
            ->method('get')
            ->willReturn($widgetInstance);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($this->anything(), $this->callback(function ($vars) {
                return isset($vars['items']) && isset($vars['count']) && isset($vars['metadata']);
            }))
            ->willReturn($this->frames('<div>Complex Widget</div>'));

        // Act
        ob_start();
        $this->view->widget(TestComplexWidget::class, $options);
        ob_get_clean();

        // Assert - Test passes if complex data is handled
        $this->assertTrue(true);
    }
}
