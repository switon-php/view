<?php

declare(strict_types=1);

namespace Switon\Viewing\Event;

use ReflectionMethod;
use Switon\Core\SenderAwareInterface;
use Switon\Eventing\Attribute\EventLevel;
use Switon\Eventing\Severity;
use Switon\Viewing\ViewInterface;
use Switon\Viewing\ViewRenderer;

/**
 * Event emitted before action output is mapped to view variables.
 *
 * Use when listeners want to seed or override template variables before the action return payload is merged.
 *
 * Log category: `switon.viewing.vars.resolving`
 *
 * @see \Switon\Viewing\ViewRenderer
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Viewing\Event\VarsResolved
 */
#[EventLevel(Severity::DEBUG)]
class VarsResolving implements SenderAwareInterface
{
    /**
     * @param ViewInterface $view The view instance
     * @param ReflectionMethod $method The action method that will return view variables
     * @param array<string, mixed> $vars Initial template vars supplied by earlier listeners
     */
    public function __construct(
        public ViewRenderer $sender,
        public ViewInterface $view,
        public ReflectionMethod $method,
        public array $vars = [],
    ) {
    }

    public function getSender(): ViewRenderer
    {
        return $this->sender;
    }
}
