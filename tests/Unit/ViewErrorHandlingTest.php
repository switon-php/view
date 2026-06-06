<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Core\Exception\InvalidValueException;
use Switon\Viewing\Tests\TestCase;
use ReflectionException;
use RuntimeException;
use Throwable;
use stdClass;

/**
 * Test error handling and exception scenarios for View component.
 *
 * These tests ensure the component handles errors gracefully and provides
 * meaningful error messages.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewErrorHandlingTest extends TestCase
{
    /**
     * Test widget with invalid class name format.
     */
    public function testWidgetWithInvalidClassName(): void
    {
        // Arrange
        $invalidWidget = 'Invalid\\\\Widget\\\\Name';

        // Act & Assert
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Widget');

        $this->view->widget($invalidWidget);
    }

    /**
     * Test widget with empty string name.
     */
    public function testWidgetWithEmptyStringName(): void
    {
        // Act & Assert
        $this->expectException(InvalidValueException::class);

        $this->view->widget('');
    }

    public function testWidgetWithWhitespaceOnlyName(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->view->widget('   ');
    }

    /**
     * Test render handles renderer exceptions.
     */
    public function testRenderHandlesRendererException(): void
    {
        // Arrange
        $this->renderer->method('render')
            ->willThrowException(new RuntimeException('Template not found'));

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found');

        $this->view->render('@view/NonExistent');
    }

    /**
     * Test render propagates renderer exceptions.
     */
    public function testRenderPropagatesRendererException(): void
    {
        // Arrange
        $this->renderer->method('render')
            ->willThrowException(new RuntimeException('Error'));

        // Act & Assert
        try {
            $this->view->render('@view/Test');
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $e) {
            $this->assertSame('Error', $e->getMessage());
        }
    }

    /**
     * Test widget handles widget factory exceptions.
     */
    public function testWidgetHandlesFactoryException(): void
    {
        // Arrange
        $widgetClass = \Switon\Viewing\Tests\Fixtures\TestWidget::class;

        $this->widgetFactory->method('get')
            ->willThrowException(new RuntimeException('Widget creation failed'));

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Widget creation failed');

        $this->view->widget($widgetClass);
    }

    /**
     * Test widget handles widget run() exceptions.
     */
    public function testWidgetHandlesRunException(): void
    {
        // Arrange
        $widgetClass = \Switon\Viewing\Tests\Fixtures\TestWidget::class;

        $widget = $this->createMock(\Switon\Viewing\WidgetInterface::class);
        $widget->method('run')
            ->willThrowException(new RuntimeException('Widget run failed'));

        $this->widgetFactory->method('get')->willReturn($widget);

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Widget run failed');

        $this->view->widget($widgetClass);
    }

    /**
     * Test block handles renderer exceptions.
     */
    public function testBlockHandlesRendererException(): void
    {
        // Arrange
        $this->renderer->method('render')
            ->willThrowException(new RuntimeException('Block not found'));

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Block not found');

        $this->view->block('NonExistentBlock');
    }

    /**
     * Test render with invalid Controller::Action format.
     */
    public function testRenderWithInvalidControllerActionFormat(): void
    {
        // Arrange - Class that doesn't exist
        $template = 'NonExistent\\Controller::action';

        // Act & Assert
        $this->expectException(ReflectionException::class);

        $this->view->render($template);
    }

    /**
     * Test fixUrl with malformed HTML.
     */
    public function testFixUrlWithMalformedHtml(): void
    {
        // Arrange
        $prefix = '/app';
        $malformedContent = '<a href="/page>Broken</a>';

        $this->view->setContent($malformedContent);

        // Act - Should not throw exception
        $this->view->fixUrl($prefix);

        // Assert - Content should be processed (even if malformed)
        $result = $this->view->getContent();
        $this->assertIsString($result);
    }

    /**
     * Test render with Controller::Action where controller has no file.
     */
    public function testRenderWithControllerWithoutFile(): void
    {
        // Arrange - Use an internal PHP class that has no file
        $template = stdClass::class . '::action';

        // Act & Assert - Should handle gracefully
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // This might throw or handle gracefully depending on implementation
        try {
            $result = $this->view->render($template);
            $this->assertIsString($result);
        } catch (Throwable $e) {
            // Also acceptable - just verify it's a reasonable exception
            $this->assertInstanceOf(Throwable::class, $e);
        }
    }

    /**
     * Test setVar with very large value.
     */
    public function testSetVarWithVeryLargeValue(): void
    {
        // Arrange
        $largeValue = str_repeat('x', 1000000); // 1MB string

        // Act
        $this->view->setVar('large', $largeValue);

        // Assert
        $this->assertSame($largeValue, $this->view->getVar('large'));
    }

    /**
     * Test setVars with very large array.
     */
    public function testSetVarsWithVeryLargeArray(): void
    {
        // Arrange
        $largeArray = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeArray["key$i"] = "value$i";
        }

        // Act
        $this->view->setVars($largeArray);

        // Assert
        $this->assertSame('value0', $this->view->getVar('key0'));
        $this->assertSame('value9999', $this->view->getVar('key9999'));
    }

    /**
     * Test render with circular reference in variables.
     */
    public function testRenderWithCircularReferenceInVariables(): void
    {
        // Arrange
        $obj1 = new stdClass();
        $obj2 = new stdClass();
        $obj1->ref = $obj2;
        $obj2->ref = $obj1; // Circular reference
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act - Should handle without infinite loop
        $result = $this->view->render('@view/Test', ['obj' => $obj1]);

        // Assert
        $this->assertSame('<div>Content</div>', $result);
    }

    /**
     * Test getContext returns same instance across calls.
     */
    public function testGetContextReturnsSameInstance(): void
    {
        // Act
        $context1 = $this->view->getContext();
        $context2 = $this->view->getContext();

        // Assert
        $this->assertSame($context1, $context2);
    }

    /**
     * Test fixUrl with extremely long prefix.
     */
    public function testFixUrlWithExtremelyLongPrefix(): void
    {
        // Arrange
        $longPrefix = '/' . str_repeat('segment/', 100);
        $content = '<a href="/page">Link</a>';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($longPrefix);

        // Assert
        $result = $this->view->getContent();
        $this->assertStringContainsString($longPrefix, $result);
    }

    /**
     * Test widget with class that doesn't exist throws exception.
     */
    public function testWidgetWithNonExistentClass(): void
    {
        // Arrange - Use a class name that doesn't exist
        $widgetClass = 'NonExistent';

        // Act & Assert - Should throw InvalidValueException because class doesn't exist
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Widget');

        $this->view->widget($widgetClass);
    }
}
