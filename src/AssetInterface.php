<?php

declare(strict_types=1);

namespace Switon\Viewing;

/**
 * Defines asset URL resolution for view templates.
 *
 * Use when template code needs framework-aware public asset URLs.
 *
 * Road-signs:
 * - get(): alias-based asset URL resolution
 *
 * @see \Switon\Viewing\Asset
 * @see \Switon\Viewing\ViewInterface
 */
interface AssetInterface
{
    /**
     * Resolve one public asset path to a template-ready URL.
     *
     * @param string $path Suffix concatenated to both <code>resolve('@public')</code> and <code>resolve('@asset')</code>.
     *     Prefer a root-relative segment such as <code>/js/app.js</code> when the asset base has no trailing slash.
     *     If the segment has no leading slash, the resolved asset base should end with <code>/</code>.
     */
    public function get(string $path): string;
}
