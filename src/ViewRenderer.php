<?php

declare(strict_types=1);

namespace Switon\Viewing;

use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionMethod;
use Switon\Core\ArrayableInterface;
use Switon\Core\Attribute\Autowired;
use Switon\Eventing\Attribute\EventListener;
use Switon\Http\Event\RequestRendering;
use Switon\Viewing\Event\VarsResolved;
use Switon\Viewing\Event\VarsResolving;

use function is_array;

/**
 * Bridges request rendering events to the view layer.
 *
 * Use when action return values should be transformed into view variables and rendered into HTTP response content.
 *
 * Road-signs:
 * - onRendering(): RequestRendering listener entry
 * - resolveViewVars(): action-return to template-vars mapping
 *
 * @see \Switon\Http\Event\RequestRendering
 * @see \Switon\Http\RequestHandler::invoke() Typical emitter
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Viewing\ViewInterface::render() Render entry
 * @see \Switon\Viewing\View
 * @see \Switon\Viewing\View::render() Default implementation
 * @see \Switon\Viewing\Event\VarsResolving
 * @see \Switon\Viewing\Event\VarsResolved
 */
class ViewRenderer
{
    #[Autowired] protected ViewInterface $view;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;

    /**
     * Handle a request-rendering pass that has not already produced response content.
     */
    #[EventListener] public function onRendering(RequestRendering $event): void
    {
        // Skip if response content is already set
        if ($event->response->hasContent()) {
            return;
        }

        $vars = $this->resolveViewVars($event->method, $event->return);
        $this->view->setVars($vars);

        // Render view
        $handler = $event->request->handler();
        $content = $this->view->render($handler, [], $event->prefix);

        // Set response content and Content-Type
        $event->response->setContent($content);
        $event->response->setHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Resolve view variables from action return value and events.
     *
     * Listener flow:
     * - VarsResolving: seed or prefill template variables
     * - action return value: merge array/arrayable/object payload
     * - VarsResolved: final mutation hook before rendering
     *
     * @return array<string, mixed>
     */
    protected function resolveViewVars(ReflectionMethod $method, mixed $return): array
    {
        // Dispatch ViewVarsResolving event - allow pre-filling view variables
        $varsEvent = new VarsResolving($this, $this->view, $method);
        $this->eventDispatcher->dispatch($varsEvent);
        $vars = $varsEvent->vars;

        // Merge action return value with pre-filled variables
        if (is_array($return)) {
            $vars = [...$vars, ...$return];
        } elseif ($return instanceof ArrayableInterface) {
            $vars = [...$vars, ...$return->toArray()];
        } elseif (is_object($return)) {
            // For other objects, store as 'data' variable
            $vars['data'] = $return;
        }

        // Dispatch ViewVarsResolved event - allow modifying final variables
        $varsEvent = new VarsResolved($this, $this->view, $method, $vars);
        $this->eventDispatcher->dispatch($varsEvent);

        return $varsEvent->vars;
    }
}
