<?php

declare(strict_types=1);

namespace Switon\Viewing;

/**
 * Defines the widget contract for reusable view fragments.
 *
 * Use when a widget should return either template variables or pre-rendered HTML from <code>run()</code>.
 *
 * Road-signs:
 * - run(): widget execution entry
 *
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Viewing\View
 */
interface WidgetInterface
{
    /**
     * Run the widget and return either template data or final HTML.
     *
     * @param array<string, mixed> $vars Variables passed to the widget
     *
     * @return array<string, mixed>|string Data for template rendering (array) or HTML string
     */
    public function run(array $vars = []): array|string;
}
