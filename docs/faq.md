# View FAQ {#view-faq}

Short answers for the most common `view` questions.

> **Prerequisites:** [View Package](README.md)

## Templates {#templates}

### How do I address templates? {#template-addressing}

Use either an explicit `@view/...` path or `Controller::Action`.

### How does `Controller::Action` resolution work? {#controller-action-resolution}

The resolver derives the path from the controller file, removes `Controller` and `Action` suffixes, and prefers a nested
template directory when it exists.

### Why does `index` work as a flat file, but `edit` still looks nested? {#index-flat-fallback}

Because the flat fallback is only for `index` when the nested directory does not exist.

## Layouts And Fragments {#layouts-and-fragments}

### How do I use or disable layouts? {#layout-control}

Use `setLayout()` to choose a layout and `disableLayout()` when an endpoint should return only fragment HTML.

### What is the difference between `widget()` and `block()`? {#widget-vs-block}

Use `widget()` for reusable fragments with PHP logic, and `block()` for template-only fragments.

### How do short widget names resolve? {#short-widget-names}

A short name such as `Menu` resolves to `App\Widget\MenuWidget` by default.

### How do asset URLs work? {#asset-helper}

`AssetInterface::get()` resolves public asset URLs and can append a short version hash for existing files.

## HTTP Integration {#http-integration}

### What does `ViewRenderer` do? {#view-renderer}

It listens to `Switon\Http\Event\RequestRendering`, maps action results to vars, renders the handler template, and
writes HTML to the response.

### What is the smallest HTTP rendering path? {#minimal-http-path}

Use `ViewMapping` on the action and return an array.
If the response already has content, `ViewRenderer` leaves it alone.

### Why did my returned object become `data`? {#object-becomes-data}

Only arrays and objects with `toArray()` are expanded automatically.

### Why is `hasVar()` false for a `null` value? {#null-hasvar}

`hasVar()` follows `isset()` semantics.

### How do short block paths resolve? {#block-paths}

A short path without `@` or `/` becomes `@view/Blocks/{Name}`; paths with `/` are used as written.

### How do widget templates resolve? {#widget-paths}

Short widget names resolve to `App\Widget\{Name}Widget`, and the widget view is found from the widget class file
location.

## Troubleshooting {#troubleshooting}

### Template not found {#template-not-found}

Check whether you used the right `@view/...` path or the right `Controller::Action` location.

### Layout is not applied {#layout-not-applied}

Check that layout is set before `render()` and was not disabled earlier in the same request context.

### URLs are not rewritten {#urls-not-rewritten}

Rewriting only runs when `rewrite` is enabled and `render(..., $prefix)` receives a non-empty prefix.

### How should I test View code? {#testing}

Test template resolution, layout behavior, variable merging, widget/block output, and `ViewRenderer` mapping separately.
