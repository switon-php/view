<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Viewing\Tests\TestCase;
use stdClass;

/**
 * Test ViewContext behavior and state management.
 *
 * These tests ensure proper context isolation and state management
 * in concurrent/async environments.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewContextTest extends TestCase
{
    /**
     * Test context persists across multiple operations.
     */
    public function testContextPersistence(): void
    {
        // Act
        $this->view->setVar('var1', 'value1');
        $this->view->setLayout('Layout1');
        $this->view->setContent('Content1');

        // Perform other operations
        $this->view->setVar('var2', 'value2');

        // Assert - All previous state should persist
        $this->assertSame('value1', $this->view->getVar('var1'));
        $this->assertSame('value2', $this->view->getVar('var2'));
        $this->assertSame('Layout1', $this->view->getContext()->layout);
        $this->assertSame('Content1', $this->view->getContent());
    }

    /**
     * Test context variables are properly merged.
     */
    public function testContextVariableMerging(): void
    {
        // Arrange
        $this->view->setVar('key1', 'value1');
        $this->view->setVar('key2', 'value2');

        // Act
        $this->view->setVars([
            'key2' => 'updated2',
            'key3' => 'value3',
        ]);

        // Assert
        $this->assertSame('value1', $this->view->getVar('key1'));
        $this->assertSame('updated2', $this->view->getVar('key2'));
        $this->assertSame('value3', $this->view->getVar('key3'));
    }

    /**
     * Test context content is properly updated.
     */
    public function testContextContentUpdate(): void
    {
        // Act
        $this->view->setContent('Initial');
        $this->assertSame('Initial', $this->view->getContent());

        $this->view->setContent('Updated');
        $this->assertSame('Updated', $this->view->getContent());
    }

    /**
     * Test context layout changes.
     */
    public function testContextLayoutChanges(): void
    {
        // Arrange
        $context = $this->view->getContext();

        // Act & Assert
        $this->view->setLayout('Layout1');
        $this->assertSame('Layout1', $context->layout);

        $this->view->setLayout('Layout2');
        $this->assertSame('Layout2', $context->layout);

        $this->view->disableLayout();
        $this->assertSame('', $context->layout);
    }

    /**
     * Test context variables with complex data types.
     */
    public function testContextComplexDataTypes(): void
    {
        // Arrange
        $array = ['a' => 1, 'b' => 2];
        $object = new stdClass();
        $object->prop = 'value';
        $closure = fn () => 'result';

        // Act
        $this->view->setVar('array', $array);
        $this->view->setVar('object', $object);
        $this->view->setVar('closure', $closure);

        // Assert
        $this->assertSame($array, $this->view->getVar('array'));
        $this->assertSame($object, $this->view->getVar('object'));
        $this->assertSame($closure, $this->view->getVar('closure'));
        $this->assertSame('result', ($this->view->getVar('closure'))());
    }

    /**
     * Test context with empty string values.
     */
    public function testContextEmptyStringValues(): void
    {
        // Act
        $this->view->setVar('empty', '');
        $this->view->setContent('');
        $this->view->setLayout('');

        // Assert
        $this->assertSame('', $this->view->getVar('empty'));
        $this->assertSame('', $this->view->getContent());
        $this->assertSame('', $this->view->getContext()->layout);
    }

    /**
     * Test getting all variables from context.
     */
    public function testGetAllVariables(): void
    {
        // Arrange
        $vars = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        // Act
        $this->view->setVars($vars);
        $allVars = $this->view->getVar();

        // Assert
        $this->assertIsArray($allVars);
        $this->assertSame($vars, $allVars);
    }

    /**
     * Test context state after render.
     */
    public function testContextStateAfterRender(): void
    {
        // Arrange
        $this->view->setVar('key', 'value');
        $this->view->setLayout('Layout');
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act
        $this->view->render('@view/Test');

        // Assert - Context state should persist after render
        $this->assertSame('value', $this->view->getVar('key'));
        $this->assertSame('Layout', $this->view->getContext()->layout);
        $this->assertSame('<div>Content</div>', $this->view->getContent());
    }
}
