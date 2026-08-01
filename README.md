# Switon View Package

[![CI](https://img.shields.io/github/actions/workflow/status/switon-php/view/ci.yml?branch=main&label=CI)](https://github.com/switon-php/view/actions/workflows/ci.yml) [![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4)](https://www.php.net/)

Switon's view layer for router-mapped HTML actions, request-local state, widgets, and layout/block rendering.

## Highlights

- **View routing:** `#[ViewMapping]` and related attributes route page actions into the HTML view flow.
- **Flexible template resolution:** `ViewInterface::render()` accepts direct template paths and `Controller::Action`
  targets.
- **Shared layout shell:** `setLayout()`, `@section`, and `@yield` let page templates reuse one wrapper.
- **Request-local view state:** `ViewContext` keeps layout, variables, and rendered content together.
- **Widget support:** reusable page parts can be built with widget contracts.
- **HTTP bridge:** `ViewRenderer` turns action return values into rendered HTML responses.

## Installation

```bash
composer require switon/view
```

## Quick Start

```php
use Switon\Routing\Attribute\RequestMapping;
use Switon\Routing\Attribute\ViewMapping;
use Switon\Viewing\ViewInterface;
use Switon\Core\Attribute\Autowired;

#[RequestMapping('/user')]
class UserController
{
    #[Autowired] protected ViewInterface $view;

    #[ViewMapping('profile')]
    public function profileAction(): array
    {
        return [
            'title' => 'Profile',
            'user' => ['name' => 'Mark'],
        ];
    }
}
```

Docs: https://github.com/switon-php/view/blob/main/docs/README.md

## License

MIT.
