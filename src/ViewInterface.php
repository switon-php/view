<?php

declare(strict_types=1);

namespace Switon\Viewing;

/**
 * Defines the view-level contract for templates, layouts, widgets, and shared variables.
 *
 * Use when controllers or listeners need HTML rendering by template path or
 * <code>Controller::Action</code> mapping with request-local layout and shared vars.
 *
 * Road-signs:
 * - render() Controller::Action resolution
 * - ViewContext layout / vars / content
 * - widget() and block() entrypoints
 *
 * @see \Switon\Viewing\View
 * @see \Switon\Viewing\ViewContext
 * @see \Switon\Viewing\ViewRenderer
 * @see \Switon\Viewing\ViewRenderer::onRendering()
 * @see \Switon\Http\Event\RequestRendering
 * @see \Switon\Viewing\WidgetInterface
 * @see \Switon\Viewing\AssetInterface
 * @see \Switon\Rendering\RendererInterface
 */
interface ViewInterface
{
    /**
     * Sets the layout template to wrap rendered content.
     *
     * The layout is rendered after the main template, with the template content available in the layout context.
     * The layout path follows the same resolution rules as <code>render()</code>.
     *
     * Guidance: Controller::Action template resolution follows the current filesystem state before choosing nested
     * or flat template paths.
     *
     * @param string $layout The layout template path (for example <code>'@view/Layouts/Default'</code> or
     *     <code>'@view/Layouts/Custom'</code>). Defaults to <code>'@view/Layouts/Default'</code>.
     *
     * @return static This instance for method chaining.
     */
    public function setLayout(string $layout = '@view/Layouts/Default'): static;

    /**
     * Disables layout rendering for subsequent render calls.
     *
     * @return static This instance for method chaining.
     */
    public function disableLayout(): static;

    /**
     * Sets a variable in the view context.
     *
     * Variables set here are available to all templates rendered in the same request context.
     *
     * @param string $name The variable name.
     * @param mixed $value The variable value.
     *
     * @return static This instance for method chaining.
     */
    public function setVar(string $name, mixed $value): static;

    /**
     * Sets multiple variables in the view context.
     *
     * Variables are merged with existing context variables. If a variable already exists, it is overwritten.
     *
     * @param array<string, mixed> $vars An associative array of variable names and values.
     *
     * @return static This instance for method chaining.
     */
    public function setVars(array $vars): static;

    /**
     * Gets a variable from the view context, or all variables if no name is provided.
     *
     * @param string|null $name The variable name, or <code>null</code> to get all variables.
     *
     * @return mixed The variable value, all variables as an array, or <code>null</code> if the variable doesn't exist.
     */
    public function getVar(?string $name = null): mixed;

    /**
     * Checks whether a view variable is set to a non-null value.
     *
     * @param string $name The variable name.
     *
     * @return bool <code>true</code> if the variable exists and is not <code>null</code>, <code>false</code> otherwise.
     */
    public function hasVar(string $name): bool;

    /**
     * Renders a template and returns the final HTML content.
     *
     * This method locates and renders a view template based on the provided template path. The template path
     * can be in <code>Controller::Action</code> format (e.g., <code>App\Controller\UserController::index</code>)
     * or a direct path (e.g., <code>@view/Layouts/Default</code>).
     *
     * **Template Location:**
     * - If template contains <code>::</code>: Resolves to <code>Views/ControllerName/ActionName</code> relative to
     *   the controller file directory. The "Controller" suffix and "Action" suffix (if present) are automatically
     *   removed. Action names are capitalized (e.g., <code>index</code> → <code>Index</code>). This works for both
     *   regular controllers (e.g., <code>app/View/User/Index</code>) and area controllers
     *   (e.g., <code>app/Areas/Admin/View/Account/Register</code>).
     * - If template is a direct path: Used as-is, with path resolution handled by the renderer. Supports
     *   <code>@view</code> alias and relative paths.
     *
     * **Rendering Process:**
     * 1. Merges provided variables with existing context variables
     * 2. Locates the template file (converts <code>Controller::Action</code> format if needed)
     * 3. Executes the main template through <code>RendererInterface::render()</code>
     * 4. Reuses the same render transaction for layout wrapping when a layout is configured
     * 5. Fixes URLs in the content (if enabled)
     * 6. Returns the final HTML content
     *
     * @param string $template The template path. Can be <code>Controller::Action</code> format (e.g.,
     *                         <code>App\Controller\UserController::index</code>) or a direct path (e.g.,
     *                         <code>@view/Layouts/Default</code>).
     * @param array<string, mixed> $vars Optional variables to pass to the template. These are merged with existing context
     *                         variables, with parameter values taking precedence.
     * @param string $prefix Optional URL prefix for fixing relative URLs in the rendered content.
     *
     * @return string The rendered HTML content.
     */
    public function render(string $template, array $vars = [], string $prefix = ''): string;

    /**
     * Renders a widget and outputs it directly.
     *
     * Widgets are reusable view components. The widget class is automatically located based on the widget name,
     * and its view template is rendered with the widget's output variables.
     *
     * @param string $widget The widget name (e.g., <code>'UserMenu'</code>) or fully qualified class name.
     *                        If not fully qualified, it's resolved as <code>App\Widget\{Widget}Widget</code>.
     * @param array<string, mixed> $options Options to pass to the widget's <code>run()</code> method.
     */
    public function widget(string $widget, array $options = []): void;

    /**
     * Renders a block template and outputs it directly.
     *
     * Blocks are reusable template fragments. If the path doesn't start with <code>@</code> and doesn't contain
     * <code>/</code>, it's automatically resolved as <code>@view/Blocks/{path}</code>.
     *
     * @param string $path The block template path (e.g., <code>'Header'</code> or <code>'@view/Blocks/Header'</code>).
     * @param array<string, mixed> $vars Variables to pass to the block template.
     */
    public function block(string $path, array $vars = []): void;

    /**
     * Sets the rendered content directly.
     *
     * This method allows you to set the content without rendering a template, useful for programmatically
     * generated content.
     *
     * @param string $content The HTML content to set.
     *
     * @return static This instance for method chaining.
     */
    public function setContent(string $content): static;

    /**
     * Gets the rendered content.
     *
     * @return string The HTML content.
     */
    public function getContent(): string;
}
