<?php

declare(strict_types=1);

namespace Switon\Viewing\Event;

use JsonSerializable;
use ReflectionMethod;
use Switon\Core\SenderAwareInterface;
use Switon\Eventing\Attribute\EventLevel;
use Switon\Eventing\Severity;
use Switon\Viewing\ViewInterface;
use Switon\Viewing\ViewRenderer;

/**
 * Event emitted after view variables are resolved from action output.
 *
 * Use when listeners want to inspect or mutate the final template variable map before rendering starts.
 *
 * @see \Switon\Viewing\ViewRenderer
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Viewing\Event\VarsResolving
 */
#[EventLevel(Severity::DEBUG)]
class VarsResolved implements JsonSerializable, SenderAwareInterface
{
    /**
     * @param ViewInterface $view The view instance
     * @param ReflectionMethod $method The action method that returned view variables
     * @param array<string, mixed> $vars Final template vars about to be rendered
     */
    public function __construct(
        public ViewRenderer $sender,
        public ViewInterface $view,
        public ReflectionMethod $method,
        public array $vars,
    ) {
    }

    public function getSender(): ViewRenderer
    {
        return $this->sender;
    }

    /**
     * Return a compact debug payload without serializing full variable contents.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'view' => $this->view::class,
            'method' => $this->method->class . '::' . $this->method->name,
            'vars' => [
                '_keys' => array_keys($this->vars),
                '_count' => count($this->vars),
            ],
        ];
    }
}
