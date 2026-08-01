# Design Rationale {#design-rationale}

`switon/view` keeps page flow separate from template execution: it owns request-local HTML assembly, while
`switon/renderer` owns the template engine.

## See also {#see-also}

- [View Package](README.md) — usage, layout control, and HTTP bridge
- [FAQ](faq.md#view-faq) — short answers for templates and fragments
- [Renderer Package](../../renderer/docs/README.md) — template engine and rendering pipeline

---

## Core Problem {#core-problem}

Page rendering tends to spread across controllers and templates.

- controllers hand-build layout state
- template lookup rules drift by module
- widget logic and fragment rendering get mixed
- state leaks in long-running runtimes

`switon/view` standardizes the page boundary first.

## Why Request-Scoped State {#request-scoped-state}

`ViewContext` keeps layout, vars, and content together for one request.

That makes the state easy to reason about in FPM and long-running workers. The trade-off is one more context layer,
but it avoids leaking page state across requests.

## Why Controller Mapping Exists {#controller-mapping}

Most page templates belong to one controller action.

`Controller::Action` resolution keeps that convention close to the controller file, while direct `@view/...` paths stay
available for shared or explicitly named templates.

## Why `ViewRenderer` Is Separate {#viewrenderer-separate}

HTTP pages often want to turn an action return value into HTML without hand-building response objects.

`ViewRenderer` handles that translation and exposes vars through `VarsResolving` and `VarsResolved`. The trade-off is
that request-mode semantics live across `view`, `http`, and `router` docs, not in one giant page-rendering chapter.

## Why Widgets And Blocks Stay Separate {#widgets-blocks-separate}

Reusable UI falls into two shapes:

- fragments with PHP logic
- fragments that are just templates

`widget()` and `block()` keep those shapes distinct instead of hiding them behind one helper.

## Deliberate Boundary {#deliberate-boundary}

`View` does not try to become a second template engine.

- `View` owns page flow, layouts, and HTTP integration
- `RendererInterface` owns actual template execution
- `AssetInterface` owns public asset URL resolution

That split keeps the package small and makes troubleshooting easier.
