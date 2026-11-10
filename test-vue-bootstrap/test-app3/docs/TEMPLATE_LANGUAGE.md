# SahilPHP Template Language

The SahilPHP template engine provides a secure, maintainable templating system with automatic escaping, template inheritance, and a deliberately small, total language.

## Overview

### Security Model

A template is **data, never code**:
- Templates are parsed and re-emitted by the compiler
- Nothing from a template reaches `eval()`, variable functions, or dynamic includes
- Templates can only read data they were given through the render context
- Templates can only call whitelisted helper functions
- Output escapes by default; raw output requires explicit syntax
- Template includes are validated to prevent path traversal attacks

### Key Features

- **Automatic escaping** - All output is HTML-escaped by default
- **Template inheritance** - Use `@extends` and `@section` for reusable layouts
- **Partials and includes** - Reuse template fragments with `@include`
- **Control structures** - Conditionals (`@if`, `@elseif`, `@else`) and loops (`@foreach`)
- **Expression language** - Safe, limited expression evaluation without arbitrary PHP
- **Compilation and caching** - Templates are compiled to PHP and cached with content-based invalidation
- **Useful error messages** - Template syntax errors include file and line information

## Template Syntax

### Echoing Output

#### Escaped Output (Default)

Use `{{ }}` to echo escaped output. This is the safest and most common way to display data.

```blade
<h1>{{ $title }}</h1>
<p>{{ $description }}</p>
```

The value is automatically HTML-escaped to prevent XSS attacks.

#### Raw Output

Use `{!! !!}` to echo raw, unescaped output. Use this **only** when you're certain the content is safe (e.g., HTML from a trusted source).

```blade
<div>{!! $trustedHtml !!}</div>
```

**Warning:** Never use raw output with user-supplied data.

#### JSON Encoding

Use `@json($var)` to encode data as JSON. This is useful for passing data to JavaScript.

```blade
<script>
    const data = @json($userData);
</script>
```

## Control Structures

### Conditionals

Use `@if`, `@elseif`, `@else`, and `@endif` for conditional rendering.

```blade
@if ($user->isAdmin())
    <p>Welcome, administrator!</p>
@elseif ($user->isModerator())
    <p>Welcome, moderator!</p>
@else
    <p>Welcome, user!</p>
@endif
```

### Loops

Use `@foreach` and `@endforeach` to iterate over arrays and objects.

```blade
@foreach ($items as $item)
    <li>{{ $item->name }}</li>
@endforeach
```

The loop variable is automatically named and scoped to prevent conflicts.

## Template Inheritance

### Defining a Layout

Layouts define the common structure of your pages. Use `@yield` to define sections that child templates can fill.

```blade
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title', 'Default Title')</title>
</head>
<body>
    <header>
        <nav>
            <!-- navigation -->
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        @yield('footer')
    </footer>
</body>
</html>
```

### Extending a Layout

Child templates use `@extends` to inherit from a layout and `@section` to define content.

```blade
<!-- resources/views/home.blade.php -->
@extends('layouts.app')

@section('title', 'Home Page')

@section('content')
    <h1>Welcome Home</h1>
    <p>This is the home page content.</p>
@endsection

@section('footer')
    <p>&copy; 2024 My Application</p>
@endsection
```

### Default Section Content

You can provide default content for a section using the `@yield` directive.

```blade
@yield('sidebar', '<p>No sidebar content</p>')
```

### Parent Section Content

Use `@parent` to append to a parent section's content instead of replacing it.

```blade
@section('styles')
    @parent
    <link rel="stylesheet" href="/custom.css">
@endsection
```

## Partials and Includes

### Including Partials

Use `@include` to include another template. The included template inherits the parent's data, but you can pass additional data.

```blade
@include('partials.header')

<div class="content">
    <!-- main content -->
</div>

@include('partials.footer')
```

### Passing Data to Partials

Pass an array of data as the second argument to `@include`.

```blade
@include('partials.user-card', ['user' => $currentUser])
```

### Include Nesting Limit

The engine limits include nesting to 32 levels to prevent infinite recursion. Exceeding this limit throws a `ViewRenderException`.

## Expression Language

The template engine supports a safe, limited expression language for evaluating conditions and loops.

### Supported Operators

- **Comparison**: `==`, `!=`, `>`, `<`, `>=`, `<=`
- **Logical**: `&&` (or `and`), `||` (or `or`), `!` (or `not`)
- **Membership**: `in` (checks if a value is in an array)
- **Ternary**: `? :` (conditional expression)
- **Null coalescing**: `??` (returns right operand if left is null)

### Examples

```blade
@if ($user->age >= 18)
    <p>Adult</p>
@endif

@if ($status == 'active' && $verified)
    <p>Account is active and verified</p>
@endif

@if ($role in ['admin', 'moderator'])
    <p>Has elevated permissions</p>
@endif

{{ $name ?? 'Anonymous' }}
```

### Accessing Data

Access array elements and object properties using dot notation.

```blade
{{ $user.name }}
{{ $post.author.email }}
{{ $settings.theme.colors.primary }}
```

### Function Calls

Only whitelisted helper functions can be called from templates. Scalar helpers
are defined in `ViewHelpers`; the `asset()` helper is provided by the render
context so it can resolve the active UI profile.

```blade
{{ count(posts) }}
{{ upper(title) }}
{{ asset('css/reports.css') }}
```

See the [Views & Asset Integration](VIEW_ASSETS.md) guide for `asset()`,
`@asset_css`, `@asset_js` and `@asset_client`.

### Array Literals

Array literals can be positional or associative, and they can nest:

```blade
@include('partials.nav', [
    'brand' => 'SahilPHP',
    'links' => [
        ['label' => 'Home', 'href' => '/'],
        ['label' => 'Health', 'href' => '/health'],
    ],
])
```

## Directives Reference

### `@extends('layout')`

Inherits from a parent layout. Can only be used once per template and not in included partials.

### `@section('name', 'content')` / `@section('name')` ... `@endsection`

Defines a section that fills a `@yield` in a parent layout.

### `@yield('name', 'default')`

Outputs a section's content or the default if not defined.

### `@parent`

Appends to the parent section's content instead of replacing it. Only valid inside `@section`.

### `@include('name', ['data' => 'value'])`

Includes another template. Data from the parent is merged with the provided data.

### `@if($condition)` ... `@elseif($condition)` ... `@else` ... `@endif`

Conditional rendering based on an expression.

### `@foreach($items as $item)` ... `@endforeach`

Iterates over an array or iterable.

### `@json($var)`

Encodes data as JSON for use in JavaScript.

### `@asset_client`

Emits the Vite HMR client in development and nothing in production.

### `@asset_css('entry', ['attributes' => '...'])`

Emits the stylesheet tag(s) for the active UI profile. `main` is the profile's
complete CSS entry set; `tailwind`, `bootstrap` and `app` are explicit entries
and are refused when the active profile did not install them.

### `@asset_js('entry', ['attributes' => '...'])`

Emits module script tag(s) for the active UI profile. CSS-only profiles emit
nothing for `main`.

### `asset('path')`

Returns an escaped-safe public URL for a file under `public/`. Use it inside
the normal escaped echo channel:

```blade
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
```

## Assets and UI Profiles

Templates should not construct `/build/assets/...` paths. Use
`@asset_css('main')` and `@asset_js('main')` in the layout, then add
page-specific files in `head` / `scripts` sections:

```blade
@extends('layouts.tailwind')

@section('head')
    <link rel="stylesheet" href="{{ asset('css/ui-kit.css') }}">
@endsection

@section('scripts')
    <script src="{{ asset('js/ui-kit.js') }}" defer></script>
@endsection
```

The active profile comes from `.sahil-ui.json` (written by
`php sahil ui:install`), with `ASSET_PROFILE` as an override. A Tailwind-only
installation cannot request Bootstrap, so the framework never loads both CSS
frameworks implicitly. See [Views & Asset Integration](VIEW_ASSETS.md) for
configuration, sample layouts and the complete helper reference.

## Rendering Templates

### Basic Rendering

Render a template from your application code:

```php
use Sahil\View\ViewEngine;

$viewEngine = app()->container()->get(ViewEngine::class);

$html = $viewEngine->render('home', [
    'title' => 'Welcome',
    'user' => $currentUser,
]);
```

### Passing Data

Pass an associative array of data to the template:

```php
$viewEngine->render('user.profile', [
    'user' => $user,
    'posts' => $posts,
    'settings' => $settings,
]);
```

### Checking Template Existence

Check if a template exists before rendering:

```php
if ($viewEngine->exists('custom.template')) {
    return $viewEngine->render('custom.template', $data);
}
```

### Getting Template Path

Get the absolute path of a template:

```php
$path = $viewEngine->path('home');
```

### Flushing Cache

Clear the compiled template cache:

```php
$count = $viewEngine->flush();
```

## Configuration

### View Directories

View directories are configured in `config/view.php`. By default, templates
are loaded from `views/`; multiple roots are searched in order, so an
application can override a package template by putting its own directory
first.

### Cache Directory

Compiled templates are stored in `storage/framework/views`. The cache is automatically invalidated when the source template changes.

## Security Best Practices

### 1. Always Use Escaped Output

Prefer `{{ }}` over `{!! !!}` for all user-supplied data.

```blade
<!-- Good -->
<p>{{ $userInput }}</p>

<!-- Bad - XSS vulnerability -->
<p>{!! $userInput !!}</p>
```

### 2. Validate Data Before Rendering

Validate and sanitize data in your application code before passing it to templates.

### 3. Use JSON Encoding for JavaScript Data

Use `@json()` to safely pass data to JavaScript instead of manually encoding.

```blade
<!-- Good -->
<script>
    const data = @json($userData);
</script>

<!-- Bad - vulnerable to XSS -->
<script>
    const data = {!! json_encode($userData) !!};
</script>
```

### 4. Limit Template Capabilities

The template engine deliberately restricts what templates can do:
- No arbitrary PHP code
- No `eval()` or variable functions
- No dynamic includes
- Whitelisted helper functions only

### 5. Use Safe Path Resolution

Template names are validated to prevent path traversal attacks. Always use template names, not file paths.

```blade
<!-- Good -->
@include('partials.header')

<!-- Bad - path traversal attempt -->
@include('../../../etc/passwd')
```

## Error Handling

### Template Not Found

Throws `ViewNotFoundException` when a template doesn't exist.

```php
try {
    $viewEngine->render('missing.template');
} catch (ViewNotFoundException $e) {
    // Handle missing template
}
```

### Syntax Errors

Template syntax errors throw `ViewSyntaxException` with file and line information.

```
ViewSyntaxException: Expected an identifier, got [.]. in [user/profile] on line 15.
```

### Render Errors

Runtime errors during rendering throw `ViewRenderException` with the template name and original error.

### Circular Dependencies

Circular `@extends` chains are detected and throw `ViewRenderException`.

### Include Depth Limit

Exceeding the 32-level include nesting limit throws `ViewRenderException`.

## Testing Templates

### Unit Testing

Test template rendering in isolation:

```php
use Sahil\View\ViewEngine;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function test_renders_template(): void
    {
        $engine = $this->createViewEngine();
        $output = $engine->render('test', ['name' => 'World']);

        $this->assertStringContainsString('Hello, World', $output);
    }
}
```

### Integration Testing

Test templates with real data and layouts:

```php
public function test_inherits_layout(): void
{
    $output = $engine->render('user.profile', ['user' => $user]);

    $this->assertStringContainsString('<!DOCTYPE html>', $output);
    $this->assertStringContainsString($user->name, $output);
}
```

## Performance

### Compilation and Caching

Templates are compiled to PHP files on first use and cached thereafter. The cache is invalidated when:
- The source template file is modified
- The source template content hash changes
- The cache is manually flushed

### Cache Storage

Compiled templates are stored in `storage/framework/views`. Ensure this directory is writable by the web server.

### Production Deployment

In production, ensure:
- The cache directory is writable
- Template files are deployed with correct permissions
- Cache is warmed up by rendering templates on first request or via a warm-up script

## Migration from Other Engines

### From Blade

SahilPHP templates use similar syntax to Blade but with important security differences:
- No arbitrary PHP (`@php`, `{!! PHP !!}` are rejected)
- Limited expression language
- Whitelisted helpers only
- Content-based cache invalidation

### From Twig

Key differences:
- Double curly braces for escaped output (like Twig)
- Single `@` for directives (not `{% %}`)
- No filters; use helper functions instead
- More restrictive expression language

## Troubleshooting

### Template Not Found

1. Verify the template name is correct
2. Check that the view directory is configured
3. Ensure the template file exists in the view directory

### Syntax Errors

1. Check line numbers in the error message
2. Verify directive syntax (`@if`, `@foreach`, etc.)
3. Ensure all blocks are properly closed (`@endif`, `@endforeach`)

### Cache Issues

1. Clear the cache: `$viewEngine->flush()`
2. Check cache directory permissions
3. Verify the cache directory exists

### Output Not Escaping

1. Ensure you're using `{{ }}` not `{!! !!}`
2. Check for raw output in included partials
3. Verify the Escaper is correctly configured

### Inheritance Not Working

1. Verify `@extends` is the first directive in the template
2. Check that section names match between `@section` and `@yield`
3. Ensure `@extends` is not used in included partials

## Example: Complete Application Layout

```blade
<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'My App')</title>
    @yield('styles')
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/">Home</a>
            <a href="/about">About</a>
            @if (session('user'))
                <a href="/profile">Profile</a>
                <a href="/logout">Logout</a>
            @else
                <a href="/login">Login</a>
            @endif
        </nav>
    </header>

    <main class="main">
        @yield('content')
    </main>

    <footer class="footer">
        <p>&copy; {{ date('Y') }} My Application</p>
        @yield('footer')
    </footer>

    @yield('scripts')
</body>
</html>
```

```blade
<!-- resources/views/home.blade.php -->
@extends('layouts.app')

@section('title', 'Home')

@section('styles')
    @parent
    <link rel="stylesheet" href="/css/home.css">
@endsection

@section('content')
    <div class="hero">
        <h1>Welcome to My App</h1>
        <p>{{ $description }}</p>
    </div>

    @if ($featuredItems)
        <div class="featured">
            <h2>Featured Items</h2>
            @foreach ($featuredItems as $item)
                <div class="item">
                    <h3>{{ $item->name }}</h3>
                    <p>{{ $item->description }}</p>
                </div>
            @endforeach
        </div>
    @endif
@endsection

@section('scripts')
    <script src="/js/home.js"></script>
@endsection
```

## Additional Resources

- View API documentation: `src/View/ViewEngine.php`
- Template compiler: `src/View/TemplateCompiler.php`
- Expression compiler: `src/View/ExpressionCompiler.php`
- Escaper implementation: `src/View/Escaper.php`
