<div align="center">
<a href="https://github.com/semperton">
<img src="https://avatars0.githubusercontent.com/u/76976189?s=140" alt="Semperton">
</a>
<h1>Semperton Routing</h1>
<p>A lightweight B-tree based routing library for PHP.<br>Supports custom validators and reverse routing.</p>
</div>
<hr>

## Beforehand

- This library does not provide the full flexibility of a regex based router.
- You can however register custom validators which do perform regex matching.
- This library does not distinguish between URLs with or without a trailing slash.

## Installation

Just use Composer:

```
composer require semperton/routing
```
Container requires PHP 7.1+

## Routes

All routes are added to a ```RouteCollection```. There are shorthand functions for every http verb and a general ```map()``` method:
```php
use Semperton\Routing\RouteCollection;

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
use Semperton\Routing\RouteMatcher;

$matcher = new RouteMatcher($routes->getTree());

$result = $matcher->match('GET', '/blog/article/3');

$result->isMatch(); // true
$result->getHandler(); // 'article-handler'
$result->getParams(); // ['id' => '3']
```

## Placeholder

You can substitute parts of a route with placeholders. They start with a colon followed by a identifier:validator combination:
```
:path -- no validator
:id:d -- digit validator
:name:w -- word validator
```

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
$routes = new RouteCollection();
$routes->get('/media/:filename:file', 'handler');

$matcher = new RouteMatcher($routes->getTree());
$matcher->setValidator('file', function (string $value) {

	$parts = explode('.', $value);

	if(count($parts) >= 2){
		return true;
	}

	return false;
});

$matcher->validate('readme.txt', 'file'); // true

$result = $matcher->match('GET', '/media/data.json');
$result->getParams(); // ['filename' => 'data.json']
```