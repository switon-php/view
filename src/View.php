<?php

declare(strict_types=1);

namespace Switon\Viewing;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use Switon\Core\Attribute\Autowired;
use Switon\Core\ContextAware;
use Switon\Core\ContextManagerInterface;
use Switon\Core\Exception\InvalidValueException;
use Switon\Rendering\RendererInterface;

use function basename;
use function class_exists;
use function dirname;
use function explode;
use function is_string;
use function preg_replace_callback;
use function str_contains;
use function str_starts_with;
use function ucfirst;

/**
 * Default implementation of <code>ViewInterface</code> with layout, widget, and block rendering support.
 *
 * Use when application code should render templates by direct path or
 * <code>Controller::Action</code> mapping with per-request view state.
 *
 * Road-signs:
 * - render(): main template + optional layout transaction
 * - resolveTemplate(): Controller::Action mapping
 * - widget() / block(): reusable fragment entrypoints
 *
 * @see \Switon\Viewing\ViewInterface
 * @see \Switon\Viewing\ViewContext
 * @see \Switon\Viewing\ViewRenderer
 * @see \Switon\Viewing\WidgetInterface
 * @see \Switon\Viewing\AssetInterface
 * @see \Switon\Rendering\RendererInterface
 * @see \Switon\Viewing\Event\VarsResolving
 * @see \Switon\Viewing\Event\VarsResolved
 */
class View implements ViewInterface, ContextAware
{
    #[Autowired] protected ContextManagerInterface $contextManager;
    #[Autowired] protected ContainerInterface $widgetFactory;
    #[Autowired] protected RendererInterface $renderer;

    #[Autowired] protected bool $rewrite = true;
    #[Autowired] protected ?string $layout = '@view/Layouts/Default';
    #[Autowired] protected string $widgetNamespace = 'App\\Widget';

    /** @var array<string, bool> Template directories that have been confirmed to exist. */
    protected array $dirs = [];

    /**
     * Return the current request-local view context.
     */
    public function getContext(): ViewContext
    {
        return $this->contextManager->getContext($this);
    }

    public function setLayout(string $layout = '@view/Layouts/Default'): static
    {
        $context = $this->getContext();

        $context->layout = $layout;

        return $this;
    }

    public function disableLayout(): static
    {
        $context = $this->getContext();

        $context->layout = '';

        return $this;
    }

    public function setVar(string $name, mixed $value): static
    {
        $context = $this->getContext();

        $context->vars[$name] = $value;

        return $this;
    }

    public function setVars(array $vars): static
    {
        $context = $this->getContext();

        $context->vars = [...$context->vars, ...$vars];

        return $this;
    }

    public function getVar(?string $name = null): mixed
    {
        $context = $this->getContext();

        if ($name === null) {
            return $context->vars;
        } else {
            return $context->vars[$name] ?? null;
        }
    }

    public function hasVar(string $name): bool
    {
        $context = $this->getContext();

        return isset($context->vars[$name]);
    }

    public function render(string $template, array $vars = [], string $prefix = ''): string
    {
        $context = $this->getContext();

        if ($vars !== []) {
            $context->vars = [...$context->vars, ...$vars];
        }

        $template = $this->resolveTemplate($template);

        // 1. Render main template and expose it to layouts via the shared frames content.
        $frames = $this->renderer->render($template, $context->vars);
        $context->content = $frames->content();

        // 2. Render layout (if any), which can read the inner content from the shared frames.
        $layoutToRender = $context->layout ?? $this->layout;
        if ($layoutToRender !== '') {
            $frames->merge(['content' => $context->content]);
            $frames = $this->renderer->render($layoutToRender, [], $frames);
            $result = $frames->content();
        } else {
            $result = $context->content;
        }

        // 3. Apply URL fixing to the final HTML and update context
        $context->content = $result;

        if ($this->rewrite) {
            $this->fixUrl($prefix);
        }

        return $context->content;
    }

    /**
     * Resolve direct template path or Controller::Action format to a renderer template path.
     *
     * Guidance: when a controller-specific view directory exists, actions resolve under that
     * directory; otherwise the controller path itself doubles as the index template.
     */
    protected function resolveTemplate(string $template): string
    {
        // Two supported structures:
        // 1. Nested: Views/Permission/ directory with Index.sword, Edit.sword files
        // 2. Flat: Views/Permission.sword file directly (only for index action)
        if (!str_contains($template, '::')) {
            return $template;
        }

        /** @var class-string $controller */
        [$controller, $action] = explode('::', $template);
        $rClass = new ReflectionClass($controller);
        $controllerFile = $rClass->getFileName();
        $dir = dirname($controllerFile, 2) . '/View/' . basename($rClass->getShortName(), 'Controller');

        // Cache only positive hit; a missing directory may appear later in the same process.
        if (($this->dirs[$dir] ?? false) !== true) {
            $this->dirs[$dir] = is_dir($dir);
        }

        $action = basename($action, 'Action');

        if ($this->dirs[$dir]) {
            return $dir . '/' . ucfirst($action);
        }

        if ($action === 'index') {
            return $dir;
        }

        return $dir . '/' . ucfirst($action);
    }

    /**
     * Rewrite root-relative HTML URLs with the current request prefix.
     */
    public function fixUrl(string $prefix = ''): void
    {
        if ($prefix === '') {
            return;
        }

        $context = $this->getContext();

        $context->content = preg_replace_callback(
            '#\b(href|src|action|data-src)=(["\'`]{1,2})/(?!/)#',
            static fn ($match) => "$match[1]=$match[2]$prefix/",
            $context->content
        );
    }

    /**
     * Render one widget immediately.
     *
     * @param string $widget Widget name (short name or full class name)
     * @param array<string, mixed> $options Variables to pass to the widget
     */
    public function widget(string $widget, array $options = []): void
    {
        if (!str_contains($widget, '\\')) {
            $widget = $this->widgetNamespace . '\\' . ucfirst($widget) . 'Widget';
        }

        if (!class_exists($widget)) {
            InvalidValueException::raise('Widget "{widget}" not found in registered widgets', ['widget' => $widget]);
        }

        $rClass = new ReflectionClass($widget);
        $widgetFile = $rClass->getFileName();
        $view = dirname($widgetFile, 2) . '/View/Widget/' . basename($rClass->getShortName(), 'Widget');

        $widgetInstance = $this->widgetFactory->get($widget);
        $vars = $widgetInstance->run($options);

        if (is_string($vars)) {
            echo $vars;
        } else {
            echo $this->renderer->render($view, $vars)->content();
        }
    }

    /**
     * Render one block template immediately.
     */
    public function block(string $path, array $vars = []): void
    {
        if (!str_starts_with($path, '@') && !str_contains($path, '/')) {
            $path = "@view/Blocks/$path";
        }

        echo $this->renderer->render($path, $vars)->content();
    }

    public function setContent(string $content): static
    {
        $context = $this->getContext();

        $context->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->getContext()->content;
    }
}
