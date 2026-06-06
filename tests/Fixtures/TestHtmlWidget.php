<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Fixtures;

use Switon\Viewing\WidgetInterface;

class TestHtmlWidget implements WidgetInterface
{
    public function run(array $vars = []): array|string
    {
        return '<div class="test-widget">' . ($vars['content'] ?? 'HTML Content') . '</div>';
    }
}
