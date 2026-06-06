<?php

declare(strict_types=1);

use Switon\Core\App;
use Switon\Viewing\AssetInterface;

if (!function_exists('asset')) {
    /**
     * @see \Switon\Viewing\AssetInterface::get()
     */
    function asset(string $path): string
    {
        return App::get(AssetInterface::class)->get($path);
    }
}
