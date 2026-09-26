<div align="center">
<a href="https://github.com/semperton">
<img width="140" src="https://raw.githubusercontent.com/semperton/.github/main/readme-logo.svg" alt="Semperton">
</a>
<h1>Semperton Routing</h1>
<p>A lightweight tree based routing library for PHP.<br>Supports custom validators and reverse routing.</p>
</div>

---

## Installation

Just use Composer:

```
composer require semperton/routing
```
Routing requires PHP 8.2+

## Routes

All routes are added to a ```RouteCollection```. There are shorthand functions for every http verb and a general ```map()``` method:
```php
use Semperton\Routing\Collection\RouteCollection;

$routes = new RouteCollection();

$routes->map(['GET', 'POST'], '/blog/article/:id:d', 'article-handler');
$routes->get('/category/product', 'product-handler');
$routes->post('/user/login', 'login-handler');

// grouping

$routes->group('/blog', function (RouteCollection $blog) {
	$blog->get('/article', 'article-handler');
	$blog->get('/category', 'category-handler');
});
```

Routes must start with a slash and must not contain empty segments (`//`).
HTTP methods are case-sensitive and must be uppercase (`GET`, `PURGE`, `VERSION-CONTROL`).

### Base path

Use a group to serve all routes under a base path. Requests outside of it never match, and `reverse()` includes it:
```php
$routes->group('/api', function (RouteCollection $api) {
	$api->get('/users/:id:d', 'user-handler', 'user');
});

$routes->reverse('user', ['id' => 7]); // '/api/users/7'
```

## Matching

The ```RouteMatcher``` is used to match a request method and path against all defined routes. It uses the route tree from ```RouteCollection``` and returns a ```MatchResult```.
The route tree (`RouteNode`) is internal and may change in any release, so always build it with `RouteCollection` or a subclass of it:
```php
use Semperton\Routing\Matcher\RouteMatcher;

$matcher = new RouteMatcher($routes);

$result = $matcher->match('GET', '/blog/article/3');

$result->isMatch(); // true
$result->getHandler(); // 'article-handler'
$result->getParams(); // ['id' => '3']
```

If the path matches but the method does not, `isMatch()` is `false` and `getMethods()` contains the allowed methods (useful for a `405` response).
`HEAD` requests fall back to the `GET` handler, unless a `HEAD` route is defined explicitly.

Pass the raw (percent-encoded) request path. It is split into segments first and each segment is decoded afterwards, so routes are written decoded (`/über`) and params are returned decoded (`a%2Fb` → `a/b`).

### Security

Params are decoded, untrusted user input. A placeholder can contain `/`, `..` or NUL bytes (`a%2F..%2Fb` → `a/../b`), and a wildcard can contain `..` segments (`/files/../../etc`).
Wildcard values join the decoded segments, so `a%2Fb/c` and `a/b/c` both give `a/b/c`.
The router does not remove dot segments. Validate params before using them in filesystem paths:
```php
$path = $result->getParams()['path'];
$base = realpath('/var/www/files');

// realpath() throws a ValueError on NUL bytes
$file = str_contains($path, "\0") ? false : realpath($base . '/' . $path);

if ($file === false || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
	// 404
}
```

## Placeholders

You can substitute parts of a route with placeholders. They start with a colon followed by an ```identifier:validator``` combination:
```
:path -- no validator
:id:d -- digit validator
:name:w -- word validator
```

Note that a placeholder MUST take up everything between two slashes e.g. ```/blog/:category/:id:d```.
Multiple substitutions like ```/:document-:id.html``` are not allowed. You should consider custom validators instead.

## Wildcards

Wildcards can be used at the end of a route (catchall handler):
```php
$routes->get('/*path', 'admin-handler');
$result = $matcher->match('GET', '/admin/users');
$result->getParams(); // ['path' => 'admin/users']
```
A validator on a wildcard (`/*path:d`) checks the whole remaining path. Wildcards must be the last segment of a route.

## Precedence

Segments are matched in the order static > placeholder > wildcard. Placeholders and wildcards are tried in the order they were added.
If a more specific branch does not lead to a match, the next one is tried:
```php
$routes->get('/blog/recent', 'recent-handler');
$routes->get('/blog/:slug/comments', 'comments-handler');

$matcher->match('GET', '/blog/recent/comments'); // 'comments-handler', ['slug' => 'recent']
```

## Validators

There are several builtin validators available:
```
:A -- letters and digits
:a -- letters
:d -- digits
:x -- hex digits
:l -- lowercase letters
:u -- uppercase letters
:w -- letters, digits and _
```
All builtin validators only accept ASCII characters (independent of the locale) and never match an empty value.

You can add custom validators to the ```RouteCollection```. A validator receives the decoded segment and MUST return a `bool`.
Validators must be defined before the routes that use them and cannot be replaced:
```php
use Semperton\Routing\Collection\RouteCollection;
use Semperton\Routing\Matcher\RouteMatcher;

$routes = new RouteCollection();
$routes->setValidator('file', fn (string $value): bool => str_contains($value, '.'));
$routes->get('/media/:filename:file', 'handler');

$routes->validate('readme.txt', 'file'); // true

$matcher = new RouteMatcher($routes);
$result = $matcher->match('GET', '/media/data.json');
$result->getParams(); // ['filename' => 'data.json']
```

Unknown validators throw an `InvalidArgumentException` when the route is added.
Placeholders never match an empty segment, wildcards may match an empty rest.

## Reverse Routing

The ```RouteCollection``` can be used to build known routes with the ```reverse()``` method.
```php
use Semperton\Routing\Collection\RouteCollection;

$routes = new RouteCollection();

// add a named route
$routes->get('/product/:id:d', 'product-handler', 'product-route');

// build it
$routes->reverse('product-route', ['id' => 42]); // '/product/42'
```
Params are passed decoded and checked against their validators, the whole path gets percent-encoded (wildcard values per segment, so slashes are kept).

## Long-running workers

Build the `RouteCollection` and `RouteMatcher` once at boot and reuse them for every request (FrankenPHP worker mode, RoadRunner, Swoole, ...).
The matcher keeps no per-request state. Building the tree is cheap, so classic PHP-FPM setups can simply build it on every request.

## Upgrading from 2.x

- PHP 8.2+ is required, `psr/http-message` 2.0 is supported.
- `RouteCollection::dump()` and `RouteCollection::fromArray()` were removed.
- `HEAD` requests use a defined `HEAD` route first and fall back to `GET`.
- `RouteMatcher::setBasePath()` was removed. Wrap your routes in `group('/api', ...)` instead.
- Wildcard routes no longer match their parent path (`/blog/*rest` does not match `/blog`).
- Wildcard validators check the whole remaining path.
- Path segments are decoded: define routes decoded, and params and validators receive decoded values.
- `setValidator()` moved from `RouteMatcher` to `RouteCollection`. Validators must be defined before the routes using them and cannot be replaced.
- HTTP methods are case-sensitive and no longer uppercased. Methods that are not uppercase (`get`) throw an `InvalidArgumentException`.
- Routes must start with a slash and must not contain empty segments (`//`). An empty route (`''`) is only allowed inside a group (`group('/api', fn ($api) => $api->get('', ...))`).
- Invalid route definitions (duplicate routes, names or params, unknown validators, ...) throw an `InvalidArgumentException`.
- Fluent methods return `static`. Update the signatures in subclasses that override them.
