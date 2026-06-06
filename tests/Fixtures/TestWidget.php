<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Fixtures;

use Switon\Viewing\WidgetInterface;

class TestWidget implements WidgetInterface
{
    public function run(array $vars = []): array|string
    {
        return [
            'title' => $vars['title'] ?? 'Default Title',
            'content' => $vars['content'] ?? 'Default Content',
        ];
    }
}
