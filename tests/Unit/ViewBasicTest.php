<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Viewing\Tests\TestCase;
use Switon\Viewing\ViewContext;

/**
 * Test cases for View basic operations.
 *
 * Tests basic view operations: setLayout(), disableLayout(), setVar(), setVars(),
 * getVar(), hasVar(), setContent(), getContent().
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewBasicTest extends TestCase
{
    /**
     * Test setting layout.
     *
     * Verifies that layout can be set and is stored in context.
     */
    public function testSetLayout(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $layout = 'CustomLayout';

        // Act
        $result = $this->view->setLayout($layout);

        // Assert
        $this->assertSame($this->view, $result, 'setLayout() should return view instance for method chaining');
        $this->assertSame($layout, $this->view->getContext()->layout, 'Layout should be set in context');
    }

    /**
     * Test setting layout with default parameter.
     *
     * Verifies that layout defaults to '@view/Layouts/Default' when no parameter is provided.
     */
    public function testSetLayoutWithDefault(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Act
        $this->view->setLayout();

        // Assert
        $this->assertSame('@view/Layouts/Default', $this->view->getContext()->layout, 'Layout should default to "@view/Layouts/Default"');
    }

    /**
     * Test disabling layout.
     *
     * Verifies that layout can be disabled by setting it to empty string.
     */
    public function testDisableLayout(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically

        // Arrange
        $this->view->setLayout('SomeLayout');

        // Act
        $result = $this->view->disableLayout();

        // Assert
        $this->assertSame($this->view, $result, 'disableLayout() should return view instance for method chaining');
        $this->assertSame('', $this->view->getContext()->layout, 'Layout should be empty string when disabled');
    }

    /**
     * Test setting a single variable.
     *
     * Verifies that variables can be set individually and are stored in context.
     */
    public function testSetVar(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $name = 'title';
        $value = 'Test Title';

        // Act
        $result = $this->view->setVar($name, $value);

        // Assert
        $this->assertSame($this->view, $result, 'setVar() should return view instance for method chaining');
        $this->assertSame($value, $this->view->getVar($name), 'Variable should be set in context');
    }

    /**
     * Test setting multiple variables.
     *
     * Verifies that multiple variables can be set at once and are merged with existing variables.
     */
    public function testSetVars(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $this->view->setVar('existing', 'Existing Value');
        $vars = [
            'title' => 'Test Title',
            'content' => 'Test Content',
        ];

        // Act
        $result = $this->view->setVars($vars);

        // Assert
        $this->assertSame($this->view, $result, 'setVars() should return view instance for method chaining');
        $this->assertSame('Existing Value', $this->view->getVar('existing'), 'Existing variable should be preserved');
        $this->assertSame('Test Title', $this->view->getVar('title'), 'New variable should be set');
        $this->assertSame('Test Content', $this->view->getVar('content'), 'New variable should be set');
    }

    /**
     * Test setting variables overwrites existing ones.
     *
     * Verifies that setVars() overwrites existing variables with the same name.
     */
    public function testSetVarsOverwritesExisting(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $this->view->setVar('title', 'Old Title');
        $vars = ['title' => 'New Title'];

        // Act
        $this->view->setVars($vars);

        // Assert
        $this->assertSame('New Title', $this->view->getVar('title'), 'Existing variable should be overwritten');
    }

    /**
     * Test getting a variable by name.
     *
     * Verifies that getVar() returns the value of a specific variable.
     */
    public function testGetVarByName(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $this->view->setVar('title', 'Test Title');

        // Act
        $result = $this->view->getVar('title');

        // Assert
        $this->assertSame('Test Title', $result, 'getVar() should return the variable value');
    }

    /**
     * Test getting a non-existent variable returns null.
     *
     * Verifies that getVar() returns null for non-existent variables.
     */
    public function testGetVarNonExistentReturnsNull(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Act
        $result = $this->view->getVar('nonExistent');

        // Assert
        $this->assertNull($result, 'getVar() should return null for non-existent variables');
    }

    /**
     * Test getting all variables.
     *
     * Verifies that getVar() without parameters returns all variables.
     */
    public function testGetVarReturnsAllVariables(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $this->view->setVar('title', 'Test Title');
        $this->view->setVar('content', 'Test Content');

        // Act
        $result = $this->view->getVar();

        // Assert
        $this->assertIsArray($result, 'getVar() without parameters should return an array');
        $this->assertSame('Test Title', $result['title'], 'Result should contain all variables');
        $this->assertSame('Test Content', $result['content'], 'Result should contain all variables');
    }

    /**
     * Test checking if variable exists.
     *
     * Verifies that hasVar() correctly identifies existing variables.
     */
    public function testHasVar(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $this->view->setVar('title', 'Test Title');

        // Act
        $result = $this->view->hasVar('title');

        // Assert
        $this->assertTrue($result, 'hasVar() should return true for existing variables');
    }

    /**
     * Test checking if non-existent variable returns false.
     *
     * Verifies that hasVar() returns false for non-existent variables.
     */
    public function testHasVarNonExistentReturnsFalse(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Act
        $result = $this->view->hasVar('nonExistent');

        // Assert
        $this->assertFalse($result, 'hasVar() should return false for non-existent variables');
    }

    /**
     * Test setting content directly.
     *
     * Verifies that content can be set directly without rendering.
     */
    public function testSetContent(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $content = '<div>Test Content</div>';

        // Act
        $result = $this->view->setContent($content);

        // Assert
        $this->assertSame($this->view, $result, 'setContent() should return view instance for method chaining');
        $this->assertSame($content, $this->view->getContent(), 'Content should be set in context');
    }

    /**
     * Test getting content.
     *
     * Verifies that getContent() returns the content from context.
     */
    public function testGetContent(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Arrange
        $content = '<div>Test Content</div>';
        $this->view->setContent($content);

        // Act
        $result = $this->view->getContent();

        // Assert
        $this->assertSame($content, $result, 'getContent() should return the content from context');
    }

    /**
     * Test getting context.
     *
     * Verifies that getContext() returns the ViewContext instance.
     */
    public function testGetContext(): void
    {
        // View is already initialized in setUp() via Container
        // No reflection needed - Container handles dependency injection automatically


        // Act
        $context = $this->view->getContext();

        // Assert
        $this->assertInstanceOf(ViewContext::class, $context, 'getContext() should return ViewContext instance');
    }
}
