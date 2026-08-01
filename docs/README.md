# View Package {#view-package}

`switon/view` renders HTML pages with request-scoped state, controller-based template resolution, and an HTTP response
bridge.

**Key features:**

- **`Controller::Action` or direct paths** — render by convention or by explicit template path
- **Request-scoped state** — keep layout, vars, and content in `ViewContext`
- **Page reuse helpers** — `widget()` for logic-bearing fragments, `block()` for template fragments
- **HTTP bridge** — `ViewRenderer` maps handler return values to HTML responses
- **Asset URLs** — `AssetInterface::get()` resolves public asset URLs

---

## Installation {#installation}

**Requirements:** PHP 8.3+ plus the framework pieces that provide core state, events, HTTP integration, and rendering (
`switon/core`, `switon/event`, `switon/http`, `switon/renderer`).

```bash
composer require switon/view
```

`Switon\Viewing\ServiceProvider` registers `ViewRenderer` and sets `@view` to `@app/View` when the alias is missing.
The Composer `autoload.files` helper also makes `asset()` available as a global shortcut for `AssetInterface::get()`.

---

## Quick Start {#quick-start}

Use `#[ViewMapping]` for the normal HTML page path. The action returns view vars and the view layer renders the page.

```php
use Switon\Core\Attribute\Autowired;
use Switon\Routing\Attribute\ViewMapping;
use Switon\Viewing\ViewInterface;

final class UserController
{
    #[Autowired] protected ViewInterface $view;

    #[ViewMapping('/profile')]
    public function profileAction(): array
    {
        $this->view->setLayout('@view/Layouts/Default');

        return [
            'title' => 'Profile',
            'user' => ['name' => 'Mark'],
        ];
    }
}
```

`ViewRenderer` turns the returned array into HTML. If a layout is active, the final response already includes it.

---

## Core Concepts {#core-concepts}

### Rendering Paths {#rendering-paths}

`render()` accepts either a direct template path or a `Controller::Action` target.

- direct paths are best for shared or explicit templates
- `Controller::Action` is best for page templates owned by one controller
- the resolver prefers a nested template directory when it exists
- `index` can fall back to a flat file when no nested directory exists

### Request State {#request-state}

`ViewContext` holds the mutable state for one request:

- `layout`
- `vars`
- `content`

Use `setVar()`, `setVars()`, `setLayout()`, `disableLayout()`, `setContent()`, and `getContent()` to work with that
state.

- `layout` starts as `null`, which means `render()` uses the `View` default layout until you set or disable it.
- `hasVar()` follows `isset()` semantics, so a key with a `null` value counts as absent.

### Widgets And Blocks {#widgets-and-blocks}

- `widget()` resolves a widget class, runs it, and either echoes returned HTML or renders returned vars
- `block()` renders a reusable template fragment and echoes it
- short widget names resolve through `App\Widget\{Name}Widget` by default
- short block names resolve to `@view/Blocks/{Name}`
- a `block()` path that already contains `/` is passed through as-is

### HTTP Bridge {#http-bridge}

`ViewRenderer` listens to `Switon\Http\Event\RequestRendering`.

- array returns become view vars
- `null` leaves the prefilled vars unchanged
- objects with `toArray()` expand into vars
- other objects become `data`
- prefilled vars are merged first, then action return values override same keys
- if the response already has content, the view bridge skips rendering
- the renderer writes HTML content and `Content-Type: text/html; charset=utf-8`
- `VarsResolving` and `VarsResolved` let listeners adjust view vars before and after merging

### Assets {#assets}

`AssetInterface::get()` turns a path into a public asset URL, and `asset()` calls the same resolver from templates and
helpers.

- existing files can gain a short version hash
- query strings stay intact
- the result is cached per path

---

## Keep in Mind {#keep-in-mind}

- `View` owns page flow, not template syntax.
- `RendererInterface` still owns template execution.
- `widget()` and `block()` echo output.
- `rewrite` only affects URL fixing when a prefix is provided.
- `ViewContext` is request-local state, not global state.

---

## Next {#next}

- **Need setup questions answered quickly:** [FAQ](faq.md#view-faq)
- **Need design trade-offs:** [Design Rationale](design.md#design-rationale)
- **Need template engine details:** [Renderer Package](https://github.com/switon-php/renderer/blob/main/docs/README.md)
