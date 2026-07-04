<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Unit;

use Switon\Core\FilesystemInterface;
use Switon\Core\PathAliasInterface;
use Switon\Viewing\Asset;
use Switon\Viewing\Tests\TestCase;

use function file_put_contents;
use function md5_file;
use function sys_get_temp_dir;
use function unlink;

/**
 * Test cases for Asset operations.
 *
 * Tests get() method with various scenarios: cached URLs, file-based URLs with version,
 * URLs with query strings, non-existent files.
 */
#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
class AssetTest extends TestCase
{
    protected Asset $asset;
    protected FilesystemInterface $filesystem;
    protected PathAliasInterface $pathAlias;
    protected string $tempDir;
    protected string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory and file for testing
        $this->tempDir = sys_get_temp_dir() . '/switon_asset_test_' . uniqid();
        mkdir($this->tempDir, 0777);
        $this->tempFile = $this->tempDir . '/test.js';
        file_put_contents($this->tempFile, 'console.log("test");');

        $this->filesystem = $this->createMock(FilesystemInterface::class);
        $this->pathAlias = $this->createMock(PathAliasInterface::class);

        $this->asset = $this->make(Asset::class, [
            'filesystem' => $this->filesystem,
            'pathAlias' => $this->pathAlias,
        ]);
    }

    protected function tearDown(): void
    {
        // Cleanup
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        parent::tearDown();
        unset($this->asset, $this->filesystem);
    }

    /**
     * Test getting asset URL for simple path.
     *
     * Verifies that get() returns asset URL for a simple path.
     */
    public function testGetSimplePath(): void
    {
        // Arrange
        $path = '/css/style.css';
        $assetBase = '/assets';
        $expectedUrl = $assetBase . $path;

        $this->pathAlias->expects($this->once())
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);
        $this->filesystem->expects($this->once())
            ->method('isFile')
            ->with('@public' . $path)
            ->willReturn(false);

        // Act
        $result = $this->asset->get($path);

        // Assert
        $this->assertSame($expectedUrl, $result, 'get() should return asset URL for simple path');
    }

    /**
     * Test getting asset URL is cached.
     *
     * Verifies that get() caches URLs and returns cached value on subsequent calls.
     */
    public function testGetCachesUrl(): void
    {
        // Arrange
        $path = '/css/style.css';
        $assetBase = '/assets';

        $this->pathAlias->expects($this->once())
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);
        $this->filesystem->expects($this->once())
            ->method('isFile')
            ->with('@public' . $path)
            ->willReturn(false);

        // Act
        $result1 = $this->asset->get($path);
        $result2 = $this->asset->get($path);

        // Assert
        $this->assertSame($result1, $result2, 'get() should return cached URL');
        $this->assertSame('/assets/css/style.css', $result1, 'URL should be correct');
    }

    /**
     * Test getting asset URL for file with version.
     *
     * Verifies that get() appends file version hash when file exists.
     */
    public function testGetWithFileVersion(): void
    {
        // Arrange
        $path = '/js/test.js';
        $assetBase = '/assets';
        $publicBase = $this->tempDir;
        $filePath = $publicBase . $path;

        // Create the file in public directory
        $fileDir = dirname($filePath);
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777);
        }
        file_put_contents($filePath, 'console.log("test");');

        $fileHash = substr(md5_file($filePath), 0, 12);
        $expectedUrl = $assetBase . $path . '?v=' . $fileHash;

        $this->pathAlias->expects($this->once())
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);
        $this->filesystem->expects($this->once())
            ->method('isFile')
            ->with('@public' . $path)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('read')
            ->with('@public' . $path)
            ->willReturn((string) file_get_contents($filePath));

        // Act
        $result = $this->asset->get($path);

        // Assert
        $this->assertSame($expectedUrl, $result, 'get() should append path and version hash when file exists');

        // Cleanup
        unlink($filePath);
        rmdir($fileDir);
    }

    /**
     * Versioned URL with a relative path works when @asset ends with a slash (otherwise concatenation would drop the separator).
     */
    public function testGetWithFileVersionRelativePathAndTrailingSlashAssetBase(): void
    {
        $path = 'js/relative.js';
        $assetBase = '/assets/';
        $publicBase = rtrim($this->tempDir, '/') . '/';
        $filePath = $publicBase . $path;

        $fileDir = dirname($filePath);
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0777, true);
        }
        file_put_contents($filePath, 'console.log("relative");');

        $fileHash = substr(md5_file($filePath), 0, 12);
        $expectedUrl = $assetBase . $path . '?v=' . $fileHash;

        $this->pathAlias->expects($this->once())
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);
        $this->filesystem->expects($this->once())
            ->method('isFile')
            ->with('@public' . $path)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('read')
            ->with('@public' . $path)
            ->willReturn((string) file_get_contents($filePath));

        $result = $this->asset->get($path);

        $this->assertSame($expectedUrl, $result);

        unlink($filePath);
        rmdir($fileDir);
    }

    /**
     * Test getting asset URL with query string.
     *
     * Verifies that get() handles paths with query strings correctly.
     */
    public function testGetWithQueryString(): void
    {
        // Arrange
        $path = '/css/style.css?param=value';
        $assetBase = '/assets';
        $expectedUrl = $assetBase . '/css/style.css?param=value';

        $this->pathAlias->expects($this->once())
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);

        // Act
        $result = $this->asset->get($path);

        // Assert
        $this->assertSame($expectedUrl, $result, 'get() should preserve query string');
    }

    /**
     * Test getting asset URL with query string ending with question mark.
     *
     * Verifies that get() handles paths ending with ? correctly.
     */
    public function testGetWithQueryStringEndingWithQuestionMark(): void
    {
        // Arrange
        $path = '/css/style.css?';
        $assetBase = '/assets';
        $expectedUrl = $assetBase . '/css/style.css';

        $this->pathAlias->expects($this->once())
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);

        // Act
        $result = $this->asset->get($path);

        // Assert
        $this->assertSame($expectedUrl, $result, 'get() should remove trailing question mark');
    }

    /**
     * Test getting asset URL for non-existent file.
     *
     * Verifies that get() returns asset URL without version when file does not exist.
     */
    public function testGetWithNonExistentFile(): void
    {
        // Arrange
        $path = '/js/nonexistent.js';
        $assetBase = '/assets';
        $expectedUrl = $assetBase . $path;

        $this->pathAlias->expects($this->once())
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);
        $this->filesystem->expects($this->once())
            ->method('isFile')
            ->with('@public' . $path)
            ->willReturn(false);

        // Act
        $result = $this->asset->get($path);

        // Assert
        $this->assertSame($expectedUrl, $result, 'get() should return URL without version for non-existent file');
    }

    /**
     * Test getting multiple different asset URLs.
     *
     * Verifies that get() handles multiple different paths correctly.
     */
    public function testGetMultipleDifferentPaths(): void
    {
        // Arrange
        $assetBase = '/assets';
        $paths = [
            '/css/style.css',
            '/js/app.js',
            '/images/logo.png',
        ];

        $this->pathAlias->expects($this->exactly(3))
            ->method('resolve')
            ->with('@asset')
            ->willReturn($assetBase);
        $this->filesystem->expects($this->exactly(3))
            ->method('isFile')
            ->willReturn(false);

        // Act
        $results = [];
        foreach ($paths as $path) {
            $results[$path] = $this->asset->get($path);
        }

        // Assert
        foreach ($paths as $path) {
            $this->assertSame($assetBase . $path, $results[$path], "URL for {$path} should be correct");
        }
    }
}
