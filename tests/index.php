<?php

declare(strict_types=1);

use Semperton\Routing\RouteCollection;
use Semperton\Routing\RouteMatcher;

require_once __DIR__ . '/../vendor/autoload.php';

$collection = new RouteCollection();
$router = new RouteMatcher($collection);

$router->addValidationFunction('json', function ($value) {
	$parts = explode('.', $value);
	if (count($parts) === 2) {
		if ($parts[1] === 'json' && ctype_alnum($parts[0])) {
			return true;
		}
	}
	return false;
	// return (bool)preg_match('/[\w\-]+\.json/', $value);
});

// $collection->get('/admin/*remain:d', 'handler-admin');
// $collection->get('/blog', 'handler-blog');
// $collection->get('/blog/:post_slug:w', 'handler-slug');
// $collection->get('/category/:id:d', 'handler-id');

// $collection->get('/category/:name/:id', 'handler-cat');
// $collection->get('/category/:file', 'handler-file');

// $collection->get('/api/collection/:collection:w/:id:d', 'collection-handler');

// $collection->group('/admin/', function (RouteCollection $admin) {

// 	$admin->get('login/*end', 'login-handler');
// 	$admin->get('logout', 'logout-handler');
// });

// $router->setBasePath('my.domain.de');

$collection->get('/static', 'handler');

$iterationCount = 1000;

$start = microtime(true);

for ($i = 0; $i < $iterationCount; $i++) {

	// $result = $router->match('GET', '/api/collection/post/55');
	// $result = $router->match('GET', 'my.domain.de/admin/login/hhhsdfsdf/hamer.json');
	$result = $router->match('GET', '/static');
}

$end = microtime(true) - $start;

var_dump($end, $result);
