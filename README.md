<div align="center">
<a href="https://github.com/semperton">
<img width="140" src="https://raw.githubusercontent.com/semperton/.github/main/readme-logo.svg" alt="Semperton">
</a>
<h1>Semperton Routing</h1>
<p>A lightweight B-tree based routing library for PHP.<br>Supports custom validators and reverse routing.</p>
</div>

---

## Installation

Just use Composer:

```
composer require semperton/routing
```
Routing requires PHP 7.4+

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

## Matching

The ```RouteMatcher``` is used to match a request method and path against all defined routes. It uses the route tree from ```RouteCollection``` and returns a ```MatchResult```:
```php
use Semperton\Routing\Matcher\RouteMatcher;

$matcher = new RouteMatcher($routes);

$result = $matcher->match('GET', '/blog/article/3');

$result->isMatch(); // true
$result->getHandler(); // 'article-handler'
$result->getParams(); // ['id' => '3']
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
Note that wildcards are always evaluated last.

## Validators

There are several builtin validators available:
```
:A -- ctype_alnum
:a -- ctype_alpha
:d -- ctype_digit
:x -- ctype_xdigit
:l -- ctype_lower
:u -- ctype_upper
:w -- ctype_alnum + _
```

You can add custom validators to the ```RouteMatcher``` for placeholder validation:
```php
use Semperton\Routing\Collection\RouteCollection;
use Semperton\Routing\Matcher\RouteMatcher;

$routes = new RouteCollection();
$routes->get('/media/:filename:file', 'handler');

$matcher = new RouteMatcher($routes);
$matcher->setValidator('file', function (string $value) {

	$parts = explode('.', $value, 2);

	if(isset($parts[1])){
		return true;
	}

	return false;
});

$matcher->validate('readme.txt', 'file'); // true

$result = $matcher->match('GET', '/media/data.json');
$result->getParams(); // ['filename' => 'data.json']
```

## Reverse Routing

The ```RouteCollection``` can be used to build known routes with the ```reverse()``` method.
```php
use Semperton\Routing\Collection\RouteCollection;

$routes = new RouteCollection();

// add a named route
$routes->get('/product/:id:d', 'product-route');

// build it
$routes->reverse('product-route', ['id' => 42]); // '/product/42'
```
