<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Viewing\Tests\TestCase;

/**
 * Test template path resolution for Controller::Action format.
 *
 * Tests both nested structure (separate files per action) and flat structure
 * (single file for all actions).
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class ViewTemplateResolutionTest extends TestCase
{
    /**
     * Test nested structure: Views/{Controller}/ directory with separate action files.
     *
     * Structure:
     * - Views/{Controller}/Index.sword
     * - Views/{Controller}/Edit.sword
     * - Views/{Controller}/Create.sword
     */
    public function testNestedStructureWithIndexAction(): void
    {
        // Create temporary nested structure
        $dir = sys_get_temp_dir() . '/switon_view_test_' . uniqid();
        $uniqueId = uniqid();
        // Class name must END with 'Controller' for basename() to strip the suffix
        // Format: TestPermission{uniqueId}Controller -> strips to TestPermission{uniqueId}
        $className = 'TestPermission' . $uniqueId . 'Controller';
        $viewDirName = 'TestPermission' . $uniqueId;

        // The View class uses basename(ShortName, 'Controller') to get the view directory
        // So for "TestPermission{uniqueId}Controller" it becomes "TestPermission{uniqueId}"
        $viewDir = $dir . '/View/' . $viewDirName;
        mkdir($viewDir, 0777, true);

        // Create mock controller with unique class name
        $controllerFile = $dir . '/Controller/' . $className . '.php';
        mkdir(dirname($controllerFile), 0777, true);
        file_put_contents($controllerFile, "<?php class {$className} {}");
        require_once $controllerFile;

        try {
            // Mock renderer to capture template path
            $capturedTemplate = null;
            $this->renderer->expects($this->exactly(2))
                ->method('render')
                ->willReturnCallback(function ($tpl) use (&$capturedTemplate) {
                    if ($capturedTemplate === null) {
                        $capturedTemplate = $tpl;
                    }
                    return $this->frames('<div>Content</div>');
                });

            // Act: render with Controller::Action format
            $this->view->render("{$className}::index");

            // Assert: should use Views/{Controller}/Index (nested structure)
            $this->assertStringContainsString('/View/TestPermission', $capturedTemplate);
            $this->assertStringContainsString('/Index', $capturedTemplate);
        } finally {
            // Cleanup
            $this->removeDirectory($dir);
        }
    }

    /**
     * Test nested structure with non-index action.
     */
    public function testNestedStructureWithEditAction(): void
    {
        // Create temporary nested structure
        $dir = sys_get_temp_dir() . '/switon_view_test_' . uniqid();
        $permissionDir = $dir . '/View/Permission';
        mkdir($permissionDir, 0777, true);

        // Create mock controller with unique class name
        $className = 'TestPermissionController' . uniqid();
        $controllerFile = $dir . '/Controller/' . $className . '.php';
        mkdir(dirname($controllerFile), 0777, true);
        file_put_contents($controllerFile, "<?php class {$className} {}");
        require_once $controllerFile;

        try {
            // Mock renderer
            $capturedTemplate = null;
            $this->renderer->expects($this->exactly(2))
                ->method('render')
                ->willReturnCallback(function ($tpl) use (&$capturedTemplate) {
                    if ($capturedTemplate === null) {
                        $capturedTemplate = $tpl;
                    }
                    return $this->frames('<div>Content</div>');
                });

            // Act: render edit action
            $this->view->render("{$className}::edit");

            // Assert: should use Views/Test.../Edit (nested structure)
            $this->assertStringContainsString('/View/Test', $capturedTemplate);
            $this->assertStringContainsString('/Edit', $capturedTemplate);
        } finally {
            // Cleanup
            $this->removeDirectory($dir);
        }
    }

    /**
     * Test flat structure: Views/Permission.sword file directly (no subdirectory).
     *
     * Structure:
     * - Views/Permission.sword (contains all CRUD logic)
     *
     * This is the bug scenario that was fixed.
     */
    public function testFlatStructureWithIndexAction(): void
    {
        // Create temporary flat structure (no Permission subdirectory)
        $dir = sys_get_temp_dir() . '/switon_view_test_' . uniqid();
        $viewsDir = $dir . '/View';
        mkdir($viewsDir, 0777, true);

        // Create mock controller with unique class name
        $className = 'TestPermissionController' . uniqid();
        $controllerFile = $dir . '/Controller/' . $className . '.php';
        mkdir(dirname($controllerFile), 0777, true);
        file_put_contents($controllerFile, "<?php class {$className} {}");
        require_once $controllerFile;

        try {
            // Mock renderer
            $capturedTemplate = null;
            $this->renderer->expects($this->exactly(2))
                ->method('render')
                ->willReturnCallback(function ($tpl) use (&$capturedTemplate) {
                    if ($capturedTemplate === null) {
                        $capturedTemplate = $tpl;
                    }
                    return $this->frames('<div>Content</div>');
                });

            // Act: render with Controller::Action format
            $this->view->render("{$className}::index");

            // Assert: should use Views/Test... (flat structure, no /Index suffix)
            $this->assertStringContainsString('/View/Test', $capturedTemplate);
            $this->assertStringNotContainsString('/Index', $capturedTemplate);
        } finally {
            // Cleanup
            $this->removeDirectory($dir);
        }
    }

    /**
     * Test flat structure with non-index action.
     *
     * This will generate Views/Permission/Edit path, which will likely fail
     * at render time, but the path resolution logic is consistent.
     */
    public function testFlatStructureWithEditAction(): void
    {
        // Create temporary flat structure (no Permission subdirectory)
        $dir = sys_get_temp_dir() . '/switon_view_test_' . uniqid();
        $viewsDir = $dir . '/View';
        mkdir($viewsDir, 0777, true);

        // Create mock controller with unique class name
        $className = 'TestPermissionController' . uniqid();
        $controllerFile = $dir . '/Controller/' . $className . '.php';
        mkdir(dirname($controllerFile), 0777, true);
        file_put_contents($controllerFile, "<?php class {$className} {}");
        require_once $controllerFile;

        try {
            // Mock renderer
            $capturedTemplate = null;
            $this->renderer->expects($this->exactly(2))
                ->method('render')
                ->willReturnCallback(function ($tpl) use (&$capturedTemplate) {
                    if ($capturedTemplate === null) {
                        $capturedTemplate = $tpl;
                    }
                    return $this->frames('<div>Content</div>');
                });

            // Act: render edit action
            $this->view->render("{$className}::edit");

            // Assert: should use Views/Test.../Edit (even though directory doesn't exist)
            $this->assertStringContainsString('/View/Test', $capturedTemplate);
            $this->assertStringContainsString('/Edit', $capturedTemplate);
        } finally {
            // Cleanup
            $this->removeDirectory($dir);
        }
    }

    /**
     * Test that template directory checks reflect filesystem changes within the same View instance.
     */
    public function testNestedTemplateDirectoryIsRecheckedAfterCreation(): void
    {
        $dir = sys_get_temp_dir() . '/switon_view_test_' . uniqid();
        $className = 'TestCacheProbe' . uniqid() . 'Controller';
        $controllerFile = $dir . '/Controller/' . $className . '.php';
        mkdir(dirname($controllerFile), 0777, true);
        file_put_contents($controllerFile, "<?php class {$className} {}");
        require_once $controllerFile;

        try {
            $baseDir = dirname((string)realpath($controllerFile), 2);
            $viewDir = $baseDir . '/View/' . basename($className, 'Controller');
            $capturedTemplates = [];

            $this->view->disableLayout();
            $this->renderer->expects($this->exactly(2))
                ->method('render')
                ->willReturnCallback(function ($tpl, $vars) use (&$capturedTemplates) {
                    $capturedTemplates[] = $tpl;
                    return $this->frames('<div>Content</div>');
                });

            $this->view->render("{$className}::index");

            mkdir($viewDir, 0777, true);

            $this->view->render("{$className}::index");

            $this->assertSame($baseDir . '/View/' . basename($className, 'Controller'), $capturedTemplates[0]);
            $this->assertSame($baseDir . '/View/' . basename($className, 'Controller') . '/Index', $capturedTemplates[1]);
        } finally {
            $this->removeDirectory($dir);
        }
    }

    /**
     * Helper to remove directory recursively.
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
