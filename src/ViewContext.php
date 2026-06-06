<?php

declare(strict_types=1);

namespace Switon\Viewing;

/**
 * Stores per-request mutable state for view rendering.
 *
 * Use when view operations must share layout, variables, and rendered content in one request context.
 *
 * Road-signs:
 * - $layout: request-local layout override
 * - $vars: shared template variables
 * - $content: latest rendered HTML
 *
 * @see \Switon\Viewing\View
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Viewing\ViewRenderer
 */
class ViewContext
{
    public ?string $layout = null;
    /** @var array<string, mixed> */
    public array $vars = [];
    public string $content = '';
}
