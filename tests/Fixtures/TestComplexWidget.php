<?php

declare(strict_types=1);

namespace Switon\Viewing\Tests\Fixtures;

use Switon\Viewing\WidgetInterface;

class TestComplexWidget implements WidgetInterface
{
    public function run(array $vars = []): array|string
    {
        return [
            'items' => $vars['items'] ?? [],
            'count' => count($vars['items'] ?? []),
            'metadata' => [
                'timestamp' => time(),
                'source' => 'test',
            ],
        ];
    }
}
