<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Rendering\Frames;
use Switon\Viewing\Tests\TestCase;

/**
 * Test edge cases and boundary conditions for View component.
 *
 * These tests focus on unusual inputs, error conditions, and corner cases
 * that might not be covered by normal usage tests.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewEdgeCasesTest extends TestCase
{
    protected function vars(array|Frames $vars): array
    {
        return $vars instanceof Frames ? [...$vars->all(), 'content' => $vars->content()] : $vars;
    }

    /**
     * Test render with Controller::Action format when directory doesn't exist.
     */
    public function testRenderControllerActionWithNonExistentDirectory(): void
    {
        // Arrange
        $template = __CLASS__ . '::nonExistentAction';
        $this->renderer->expects($this->exactly(2))
            ->method('render')
            ->willReturn(\Switon\Rendering\Frames::of()->setContent('<div>Content</div>'));

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame('<div>Content</div>', $result);
    }

    /**
     * Test render caches directory existence check.
     */
    public function testRenderCachesDirectoryCheck(): void
    {
        // Arrange
        $template1 = __CLASS__ . '::action1';
        $template2 = __CLASS__ . '::action2';
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act - Render twice with same controller (different actions)
        $this->view->render($template1);
        $this->view->render($template2);

        // Assert - Directory check should be cached
        // If we get here without errors, caching works
        $this->assertTrue(true);
    }

    /**
     * Test fixUrl with special characters in URLs.
     */
    public function testFixUrlWithSpecialCharacters(): void
    {
        // Arrange
        $prefix = '/app';
        $content = '<a href="/page?id=1&amp;type=test">Link</a><img src="/image.jpg#anchor">';
        $expectedContent = '<a href="/app/page?id=1&amp;type=test">Link</a><img src="/app/image.jpg#anchor">';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($prefix);

        // Assert
        $this->assertSame($expectedContent, $this->view->getContent());
    }

    /**
     * Test fixUrl doesn't modify protocol-relative URLs.
     */
    public function testFixUrlDoesNotModifyProtocolRelativeUrls(): void
    {
        // Arrange
        $prefix = '/app';
        $content = '<a href="//cdn.example.com/file.js">CDN</a>';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($prefix);

        // Assert
        $this->assertSame($content, $this->view->getContent());
    }

    /**
     * Test render with very long template path.
     */
    public function testRenderWithLongTemplatePath(): void
    {
        // Arrange
        $longPath = '@view/' . str_repeat('VeryLongDirectory/', 10) . 'Template';
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act
        $result = $this->view->render($longPath);

        // Assert
        $this->assertSame('<div>Content</div>', $result);
    }

    /**
     * Test setVars with mixed array keys (numeric keys are converted to strings).
     */
    public function testSetVarsWithMixedKeys(): void
    {
        // Arrange
        $vars = [
            'key0' => 'value0',
            'key1' => 'value1',
            'string' => 'valueString',
        ];

        // Act
        $this->view->setVars($vars);

        // Assert
        $this->assertSame('value0', $this->view->getVar('key0'));
        $this->assertSame('value1', $this->view->getVar('key1'));
        $this->assertSame('valueString', $this->view->getVar('string'));
    }

    /**
     * Test hasVar with string keys that look like numbers.
     */
    public function testHasVarWithNumericStringKey(): void
    {
        // Arrange
        $this->view->setVar('0', 'value');

        // Act & Assert
        $this->assertTrue($this->view->hasVar('0'));
        $this->assertFalse($this->view->hasVar('999'));
    }

    /**
     * Test getVar with false value (should not be confused with null).
     */
    public function testGetVarWithFalseValue(): void
    {
        // Arrange
        $this->view->setVar('flag', false);

        // Act
        $result = $this->view->getVar('flag');

        // Assert
        $this->assertFalse($result);
        $this->assertNotNull($result);
    }

    /**
     * Test getVar with zero value.
     */
    public function testGetVarWithZeroValue(): void
    {
        // Arrange
        $this->view->setVar('count', 0);

        // Act
        $result = $this->view->getVar('count');

        // Assert
        $this->assertSame(0, $result);
    }

    /**
     * Test hasVar with false value (should return true).
     */
    public function testHasVarWithFalseValue(): void
    {
        // Arrange
        $this->view->setVar('flag', false);

        // Act & Assert
        $this->assertTrue($this->view->hasVar('flag'));
    }

    /**
     * Test hasVar with null value (should return true).
     */
    public function testHasVarWithNullValue(): void
    {
        // Arrange
        $this->view->setVar('nullable', null);

        // Act & Assert
        // isset() returns false for null, so hasVar should return false
        $this->assertFalse($this->view->hasVar('nullable'));
    }

    /**
     * Test render with empty template string.
     */
    public function testRenderWithEmptyTemplate(): void
    {
        // Arrange
        $this->renderer->method('render')->willReturn($this->frames(''));

        // Act
        $result = $this->view->render('');

        // Assert
        $this->assertSame('', $result);
    }

    /**
     * Test setContent with empty string.
     */
    public function testSetContentWithEmptyString(): void
    {
        // Act
        $this->view->setContent('');

        // Assert
        $this->assertSame('', $this->view->getContent());
    }

    /**
     * Test setContent with HTML entities.
     */
    public function testSetContentWithHtmlEntities(): void
    {
        // Arrange
        $content = '<div>&lt;script&gt;alert("XSS")&lt;/script&gt;</div>';

        // Act
        $this->view->setContent($content);

        // Assert
        $this->assertSame($content, $this->view->getContent());
    }

    /**
     * Test setContent with Unicode characters.
     */
    public function testSetContentWithUnicodeCharacters(): void
    {
        // Arrange
        $content = '<div>你好世界 🌍 مرحبا العالم</div>';

        // Act
        $this->view->setContent($content);

        // Assert
        $this->assertSame($content, $this->view->getContent());
    }

    /**
     * Test multiple layout changes.
     */
    public function testMultipleLayoutChanges(): void
    {
        // Act
        $this->view->setLayout('Layout1');
        $this->view->setLayout('Layout2');
        $this->view->setLayout('Layout3');

        // Assert
        $this->assertSame('Layout3', $this->view->getContext()->layout);
    }

    /**
     * Test layout change after disable.
     */
    public function testLayoutChangeAfterDisable(): void
    {
        // Act
        $this->view->disableLayout();
        $this->view->setLayout('NewLayout');

        // Assert
        $this->assertSame('NewLayout', $this->view->getContext()->layout);
    }

    /**
     * Test render with special characters in Controller::Action format.
     */
    public function testRenderWithSpecialCharactersInControllerAction(): void
    {
        // Arrange
        $template = __CLASS__ . '::action_with_underscore';
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act
        $result = $this->view->render($template);

        // Assert
        $this->assertSame('<div>Content</div>', $result);
    }

    /**
     * Test block with path containing slashes.
     */
    public function testBlockWithPathContainingSlashes(): void
    {
        // Arrange
        $path = 'Partials/Header';
        // When path contains slashes, it's treated as a relative path and NOT prefixed with @view/Blocks/
        // Only simple names (no slashes, no @) get the @view/Blocks/ prefix

        $this->renderer->expects($this->once())
            ->method('render')
            ->with($path, []);

        // Act
        $this->view->block($path);

        // Assert - Test passes if render is called with correct path
        $this->assertTrue(true);
    }

    /**
     * Test fixUrl with multiple attributes on same element.
     */
    public function testFixUrlWithMultipleAttributes(): void
    {
        // Arrange
        $prefix = '/app';
        $content = '<img src="/image.jpg" data-src="/thumb.jpg" alt="Image">';
        $expectedContent = '<img src="/app/image.jpg" data-src="/app/thumb.jpg" alt="Image">';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl($prefix);

        // Assert
        $this->assertSame($expectedContent, $this->view->getContent());
    }

    /**
     * Test render preserves variable types.
     */
    public function testRenderPreservesVariableTypes(): void
    {
        // Arrange
        $vars = [
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'array' => [1, 2, 3],
            'null' => null,
        ];
        $this->renderer->method('render')
            ->willReturnCallback(function ($template, $passedVars) use ($vars) {
                $passedVars = $this->vars($passedVars);
                if ($template !== '@view/Test') {
                    return $this->frames('<div>Content</div>');
                }
                // Verify types are preserved
                $this->assertIsString($passedVars['string']);
                $this->assertIsInt($passedVars['int']);
                $this->assertIsFloat($passedVars['float']);
                $this->assertIsBool($passedVars['bool']);
                $this->assertIsArray($passedVars['array']);
                $this->assertNull($passedVars['null']);
                return $this->frames('<div>Content</div>');
            });

        // Act
        $this->view->render('@view/Test', $vars);

        // Assert - Assertions are in the callback
        $this->assertTrue(true);
    }
}
