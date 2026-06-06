<?php

declare(strict_types=1);

namespace Switon\Viewing;

use Switon\Core\Attribute\Autowired;
use Switon\Core\PathAliasInterface;

use function is_file;
use function md5_file;
use function rtrim;
use function str_contains;
use function substr;

/**
 * Default asset URL resolver for view templates.
 *
 * Use when template asset paths should be resolved from aliases and optionally receive a file-hash version suffix.
 *
 * Road-signs:
 * - get(): alias resolution + optional cache-busting hash
 *
 * @see \Switon\Viewing\AssetInterface
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Core\PathAliasInterface
 */
class Asset implements AssetInterface
{
    #[Autowired] protected PathAliasInterface $pathAlias;

    /** @var array<string, string> Cached asset URLs keyed by path */
    protected array $urls = [];

    /**
     * Resolve one asset URL and cache the result by request/process path key.
     */
    public function get(string $path): string
    {
        if (($url = $this->urls[$path] ?? null) === null) {
            $assetBase = $this->pathAlias->resolve('@asset');
            if (str_contains($path, '?')) {
                $url = $assetBase . rtrim($path, '?');
            } elseif (is_file($file = $this->pathAlias->resolve('@public') . $path)) {
                $url = $assetBase . $path . '?v=' . substr(md5_file($file), 0, 12);
            } else {
                $url = $assetBase . $path;
            }

            $this->urls[$path] = $url;
        }

        return $url;
    }
}
