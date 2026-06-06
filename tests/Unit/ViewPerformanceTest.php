<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Viewing\Tests\TestCase;

/**
 * Test View component performance and scalability.
 *
 * These tests ensure the component handles high-load scenarios efficiently.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewPerformanceTest extends TestCase
{
    /**
     * Test rendering with many variables.
     */
    public function testRenderWithManyVariables(): void
    {
        // Arrange
        $vars = [];
        for ($i = 0; $i < 1000; $i++) {
            $vars["var$i"] = "value$i";
        }
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act
        $result = $this->view->render('@view/Test', $vars);

        // Assert
        $this->assertSame('<div>Content</div>', $result);
        $this->assertSame('value0', $this->view->getVar('var0'));
        $this->assertSame('value999', $this->view->getVar('var999'));
    }

    /**
     * Test multiple sequential renders.
     */
    public function testMultipleSequentialRenders(): void
    {
        // Arrange
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act & Assert
        for ($i = 0; $i < 100; $i++) {
            $result = $this->view->render('@view/Test', ['index' => $i]);
            $this->assertSame('<div>Content</div>', $result);
            $this->assertSame($i, $this->view->getVar('index'));
        }
    }

    /**
     * Test setVars with incremental updates.
     */
    public function testIncrementalVariableUpdates(): void
    {
        // Act
        for ($i = 0; $i < 100; $i++) {
            $this->view->setVar("key$i", "value$i");
        }

        // Assert
        $allVars = $this->view->getVar();
        $this->assertCount(100, $allVars);
        $this->assertSame('value0', $allVars['key0']);
        $this->assertSame('value99', $allVars['key99']);
    }

    /**
     * Test fixUrl with large content.
     */
    public function testFixUrlWithLargeContent(): void
    {
        // Arrange
        $links = [];
        for ($i = 0; $i < 1000; $i++) {
            $links[] = "<a href=\"/page$i\">Link $i</a>";
        }
        $content = implode("\n", $links);

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl('/app');

        // Assert
        $result = $this->view->getContent();
        $this->assertStringContainsString('href="/app/page0"', $result);
        $this->assertStringContainsString('href="/app/page999"', $result);
    }

    /**
     * Test variable overwriting performance.
     */
    public function testVariableOverwritingPerformance(): void
    {
        // Arrange
        $this->view->setVars(['key' => 'initial']);

        // Act - Overwrite same variable many times
        for ($i = 0; $i < 100; $i++) {
            $this->view->setVar('key', "value$i");
        }

        // Assert - Should have final value
        $this->assertSame('value99', $this->view->getVar('key'));
    }

    /**
     * Test layout switching performance.
     */
    public function testLayoutSwitchingPerformance(): void
    {
        // Act
        for ($i = 0; $i < 100; $i++) {
            $this->view->setLayout("Layout$i");
        }

        // Assert
        $this->assertSame('Layout99', $this->view->getContext()->layout);
    }

    /**
     * Test content updates with large strings.
     */
    public function testContentUpdatesWithLargeStrings(): void
    {
        // Arrange
        $largeContent = str_repeat('<div>Content</div>', 10000);

        // Act
        $this->view->setContent($largeContent);

        // Assert
        $this->assertSame($largeContent, $this->view->getContent());
        $this->assertSame(strlen($largeContent), strlen($this->view->getContent()));
    }

    /**
     * Test hasVar performance with many variables.
     */
    public function testHasVarPerformanceWithManyVariables(): void
    {
        // Arrange
        $vars = [];
        for ($i = 0; $i < 1000; $i++) {
            $vars["var$i"] = "value$i";
        }
        $this->view->setVars($vars);

        // Act & Assert
        $this->assertTrue($this->view->hasVar('var0'));
        $this->assertTrue($this->view->hasVar('var500'));
        $this->assertTrue($this->view->hasVar('var999'));
        $this->assertFalse($this->view->hasVar('var1000'));
    }

    /**
     * Test render with deeply nested arrays.
     */
    public function testRenderWithDeeplyNestedArrays(): void
    {
        // Arrange
        $nested = ['level1' => ['level2' => ['level3' => ['level4' => 'value']]]];
        $this->renderer->method('render')->willReturn($this->frames('<div>Content</div>'));

        // Act
        $result = $this->view->render('@view/Test', ['data' => $nested]);

        // Assert
        $this->assertSame('<div>Content</div>', $result);
        $data = $this->view->getVar('data');
        $this->assertSame('value', $data['level1']['level2']['level3']['level4']);
    }

    /**
     * Test fixUrl with mixed content types.
     */
    public function testFixUrlWithMixedContent(): void
    {
        // Arrange
        $content = '
            <a href="/link1">Link</a>
            <img src="/image.jpg" />
            <form action="/submit">
                <input type="hidden" value="/path" />
            </form>
            <script src="/script.js"></script>
            <link href="/style.css" />
            <div data-src="/data.json"></div>
        ';

        $this->view->setContent($content);

        // Act
        $this->view->fixUrl('/app');

        // Assert
        $result = $this->view->getContent();
        $this->assertStringContainsString('href="/app/link1"', $result);
        $this->assertStringContainsString('src="/app/image.jpg"', $result);
        $this->assertStringContainsString('action="/app/submit"', $result);
        $this->assertStringContainsString('src="/app/script.js"', $result);
        $this->assertStringContainsString('href="/app/style.css"', $result);
        $this->assertStringContainsString('data-src="/app/data.json"', $result);
    }
}
