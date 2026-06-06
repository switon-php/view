<?php

declare(strict_types=1);

namespace Switon\Viewing\Event;

use ReflectionMethod;
use Switon\Eventing\Attribute\EventLevel;
use Switon\Eventing\Severity;
use Switon\Viewing\ViewInterface;

/**
 * Event emitted before action output is mapped to view variables.
 *
 * Use when listeners want to seed or override template variables before the action return payload is merged.
 *
 * @see \Switon\Viewing\ViewRenderer
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Viewing\Event\VarsResolved
 */
#[EventLevel(Severity::DEBUG)]
class VarsResolving
{
    /**
     * @param ViewInterface $view The view instance
     * @param ReflectionMethod $method The action method that will return view variables
     * @param array<string, mixed> $vars Initial template vars supplied by earlier listeners
     */
    public function __construct(
        public ViewInterface    $view,
        public ReflectionMethod $method,
        public array            $vars = []
    ) {

    }
}
