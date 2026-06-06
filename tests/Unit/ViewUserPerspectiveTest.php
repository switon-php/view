<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Psr\Container\ContainerInterface;
use Switon\Core\ContextManagerInterface;
use Switon\Rendering\RendererInterface;
use Switon\Testing\Container;
use Switon\Viewing\Tests\TestCase;
use Switon\Viewing\View;
use Switon\Viewing\ViewContext;
use Switon\Viewing\ViewInterface;
use RuntimeException;
use stdClass;

/**
 * Test cases from user's perspective.
 *
 * These tests use ONLY public API methods and create View instances through Container
 * (as users would in real applications). No reflection or internal state access.
 *
 * Purpose: Test vulnerabilities and edge cases that users might encounter.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewUserPerspectiveTest extends TestCase
{
    protected Container $container;
    protected ViewInterface $view;
    protected ContextManagerInterface $contextManager;
    protected RendererInterface $renderer;
    protected ContainerInterface $widgetFactory;

    protected function setUp(): void
    {
        parent::setUp();

        // Use pre-configured test container (ContextManagerInterface is already registered but will be replaced with mock)
        $this->container = new Container();

        // Create mocks for dependencies
        // contextManager needs method configuration, so use mock
        $this->contextManager = $this->createMock(ContextManagerInterface::class);
        // renderer and widgetFactory are only used as properties in most tests, use stubs
        $this->renderer = $this->createStub(RendererInterface::class);
        $this->widgetFactory = $this->createStub(ContainerInterface::class);

        // Register dependencies in container (replace default ContextManager with mock)
        $this->container->set(ContextManagerInterface::class, $this->contextManager);
        $this->container->set(RendererInterface::class, $this->renderer);
        $this->container->set(ContainerInterface::class, $this->widgetFactory);

        // Setup context manager to return same context instance (realistic behavior)
        $context = new ViewContext();
        $this->contextManager->method('getContext')
            ->willReturn($context);

        // Create View instance using container (realistic way)
        $this->view = $this->container->make(View::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->container, $this->view, $this->contextManager, $this->renderer, $this->widgetFactory);
    }

    // ============================================================================
    // Normal Usage Tests - Happy Path
    // ============================================================================

    /**
     * Test basic view variable setting and retrieval.
     *
     * User scenario: Set a variable and retrieve it.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetAndGetVariable(): void
    {
        // Act - User sets a variable
        $this->view->setVar('title', 'My Page Title');

        // Assert - User retrieves the variable
        $this->assertSame('My Page Title', $this->view->getVar('title'));
    }

    /**
     * Test method chaining with setVar.
     *
     * User scenario: Chain multiple setVar calls.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarMethodChaining(): void
    {
        // Act - User chains multiple setVar calls
        $result = $this->view
            ->setVar('title', 'Page Title')
            ->setVar('content', 'Page Content')
            ->setVar('author', 'John Doe');

        // Assert - Method chaining returns view instance
        $this->assertSame($this->view, $result);
        $this->assertSame('Page Title', $this->view->getVar('title'));
        $this->assertSame('Page Content', $this->view->getVar('content'));
        $this->assertSame('John Doe', $this->view->getVar('author'));
    }

    /**
     * Test setting multiple variables at once.
     *
     * User scenario: Set multiple variables using setVars.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVars(): void
    {
        // Act - User sets multiple variables
        $vars = [
            'title' => 'Home Page',
            'description' => 'Welcome to our site',
            'keywords' => 'home, welcome, site',
        ];
        $result = $this->view->setVars($vars);

        // Assert - All variables are set
        $this->assertSame($this->view, $result);
        $this->assertSame('Home Page', $this->view->getVar('title'));
        $this->assertSame('Welcome to our site', $this->view->getVar('description'));
        $this->assertSame('home, welcome, site', $this->view->getVar('keywords'));
    }

    /**
     * Test getting all variables.
     *
     * User scenario: Get all variables at once.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testGetAllVariables(): void
    {
        // Arrange - User sets multiple variables
        $this->view->setVar('var1', 'value1');
        $this->view->setVar('var2', 'value2');

        // Act - User gets all variables
        $allVars = $this->view->getVar();

        // Assert - All variables are returned
        $this->assertIsArray($allVars);
        $this->assertArrayHasKey('var1', $allVars);
        $this->assertArrayHasKey('var2', $allVars);
        $this->assertSame('value1', $allVars['var1']);
        $this->assertSame('value2', $allVars['var2']);
    }

    /**
     * Test layout setting and disabling.
     *
     * User scenario: Set layout, then disable it.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testLayoutManagement(): void
    {
        // Act - User sets layout
        $result1 = $this->view->setLayout('CustomLayout');

        // Assert - Layout is set
        $this->assertSame($this->view, $result1);

        // Act - User disables layout
        $result2 = $this->view->disableLayout();

        // Assert - Layout is disabled
        $this->assertSame($this->view, $result2);
    }

    /**
     * Test content setting and retrieval.
     *
     * User scenario: Set content directly and retrieve it.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetAndGetContent(): void
    {
        // Act - User sets content
        $content = '<div>Hello World</div>';
        $result = $this->view->setContent($content);

        // Assert - Content is set and retrievable
        $this->assertSame($this->view, $result);
        $this->assertSame($content, $this->view->getContent());
    }

    // ============================================================================
    // Edge Cases and Boundary Conditions
    // ============================================================================

    /**
     * Test getting non-existent variable returns null.
     *
     * Edge case: Variable doesn't exist.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testGetNonExistentVariableReturnsNull(): void
    {
        // Act - User tries to get non-existent variable
        $result = $this->view->getVar('nonExistent');

        // Assert - Returns null
        $this->assertNull($result);
    }

    /**
     * Test hasVar with non-existent variable.
     *
     * Edge case: Check if non-existent variable exists.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testHasVarWithNonExistentVariable(): void
    {
        // Act - User checks if non-existent variable exists
        $result = $this->view->hasVar('nonExistent');

        // Assert - Returns false
        $this->assertFalse($result);
    }

    /**
     * Test setting variable with empty string name.
     *
     * Edge case: Empty string as variable name.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarWithEmptyStringName(): void
    {
        // Act - User sets variable with empty string name
        $this->view->setVar('', 'value');

        // Assert - Variable is set (empty string is valid key)
        $this->assertSame('value', $this->view->getVar(''));
    }

    /**
     * Test setting variable with null value.
     *
     * Edge case: Null value.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarWithNullValue(): void
    {
        // Act - User sets variable with null value
        $this->view->setVar('nullable', null);

        // Assert - Variable is set to null
        $this->assertNull($this->view->getVar('nullable'));
    }

    /**
     * Test setting variable with array value.
     *
     * Edge case: Complex data types.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarWithArrayValue(): void
    {
        // Act - User sets variable with array
        $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];
        $this->view->setVar('arrayVar', $arrayValue);

        // Assert - Array is stored correctly
        $result = $this->view->getVar('arrayVar');
        $this->assertIsArray($result);
        $this->assertSame($arrayValue, $result);
    }

    /**
     * Test setting variable with object value.
     *
     * Edge case: Object value.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarWithObjectValue(): void
    {
        // Arrange - Create a simple object
        $object = new stdClass();
        $object->property = 'value';

        // Act - User sets variable with object
        $this->view->setVar('objectVar', $object);

        // Assert - Object is stored correctly
        $result = $this->view->getVar('objectVar');
        $this->assertIsObject($result);
        $this->assertSame($object, $result);
    }

    /**
     * Test setVars with empty array.
     *
     * Edge case: Empty array.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarsWithEmptyArray(): void
    {
        // Arrange - User has existing variables
        $this->view->setVar('existing', 'value');

        // Act - User sets empty array
        $this->view->setVars([]);

        // Assert - Existing variables are preserved (merge with empty array)
        $this->assertSame('value', $this->view->getVar('existing'));
    }

    /**
     * Test setVars overwrites existing variables.
     *
     * Edge case: Variable overwriting.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarsOverwritesExistingVariables(): void
    {
        // Arrange - User has existing variables
        $this->view->setVar('title', 'Old Title');
        $this->view->setVar('content', 'Old Content');

        // Act - User sets new variables with same names
        $this->view->setVars([
            'title' => 'New Title',
            'content' => 'New Content',
        ]);

        // Assert - Variables are overwritten
        $this->assertSame('New Title', $this->view->getVar('title'));
        $this->assertSame('New Content', $this->view->getVar('content'));
    }

    // ============================================================================
    // Error Handling and Exception Scenarios
    // ============================================================================

    /**
     * Test widget with non-existent class throws exception.
     *
     * Error scenario: Widget class doesn't exist.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testWidgetWithNonExistentClassThrowsException(): void
    {
        // Arrange - Widget class doesn't exist (class_exists returns false)
        // The widget() method will call InvalidValueException::raise() internally

        // Act & Assert - User tries to render non-existent widget
        $this->expectException(\Switon\Core\Exception\InvalidValueException::class);
        $this->expectExceptionMessage('Widget');

        $this->view->widget('NonExistentWidget');
    }

    /**
     * Test render with invalid template path.
     *
     * Error scenario: Template doesn't exist (renderer throws exception).
     *
     * Note: This test creates a new container with mock renderer, which is still
     * from user perspective as users configure dependencies through container.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testRenderWithInvalidTemplateThrowsException(): void
    {
        // Arrange - Create mock with expectations for this test
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')
            ->willThrowException(new RuntimeException('Template not found'));

        // Create a new container with the mock renderer (user would do this in real app)
        $container = new Container();
        $context = new ViewContext();
        $contextManager = $this->createMock(ContextManagerInterface::class);
        $contextManager->method('getContext')->willReturn($context);
        $widgetFactory = $this->createStub(ContainerInterface::class);

        $container->set(ContextManagerInterface::class, $contextManager);
        $container->set(RendererInterface::class, $renderer);
        $container->set(ContainerInterface::class, $widgetFactory);

        $view = $container->make(View::class);

        // Act & Assert - User tries to render invalid template
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found');

        $view->render('@view/NonExistent/Template');
    }

    // ============================================================================
    // State Management Tests
    // ============================================================================

    /**
     * Test variable persistence across multiple operations.
     *
     * State management: Variables persist across method calls.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testVariablePersistence(): void
    {
        // Arrange - User sets variables
        $this->view->setVar('var1', 'value1');
        $this->view->setVar('var2', 'value2');

        // Act - User performs other operations
        $this->view->setLayout('TestLayout');
        $this->view->setContent('Test Content');

        // Assert - Variables are still available
        $this->assertSame('value1', $this->view->getVar('var1'));
        $this->assertSame('value2', $this->view->getVar('var2'));
    }

    /**
     * Test multiple setVar calls with same name.
     *
     * State management: Variable overwriting.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testMultipleSetVarCallsOverwriteValue(): void
    {
        // Act - User sets same variable multiple times
        $this->view->setVar('title', 'First Title');
        $this->view->setVar('title', 'Second Title');
        $this->view->setVar('title', 'Third Title');

        // Assert - Last value is kept
        $this->assertSame('Third Title', $this->view->getVar('title'));
    }

    /**
     * Test setVars merges with existing variables.
     *
     * State management: Variable merging.
     */
    #[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
    public function testSetVarsMergesWithExistingVariables(): void
    {
        // Arrange - User has existing variables
        $this->view->setVar('existing1', 'value1');
        $this->view->setVar('existing2', 'value2');

        // Act - User sets new variables (some new, some existing)
        $this->view->setVars([
            'existing2' => 'newValue2', // Overwrites existing
            'newVar' => 'newValue',    // New variable
        ]);

        // Assert - Existing variables are preserved, new ones added, overwritten ones updated
        $this->assertSame('value1', $this->view->getVar('existing1'));
        $this->assertSame('newValue2', $this->view->getVar('existing2'));
        $this->assertSame('newValue', $this->view->getVar('newVar'));
    }
}
