<?php

declare(strict_types=1);

use Semperton\Routing\RouteCollection;
use Semperton\Routing\RouteMatcher;

require_once __DIR__ . '/../vendor/autoload.php';


$collection = new RouteCollection((function () {

	$cacheFile = __DIR__ . '/routes.php';

	if (file_exists($cacheFile)) {

		$tree = require $cacheFile;
		return $tree;
	}

	$col = new RouteCollection();

	$col->get('/admin/*remain', 'admin-handler');
	$col->get('/blog', 'blog-handler');
	$col->get('/blog/:post_slug:w', 'post-handler');
	$col->get('/category/:name:w/:id:d', 'category-handler');

	$col->group('/user-', function (RouteCollection $user) {

		$user->get('login', 'login-handler');
		$user->get('logout', 'logout-handler');
	});

	$tree = $col->getTree();
	$data = str_replace("\n", '', var_export($tree, true));
	file_put_contents($cacheFile, "<?php return $data;");

	return $tree;
})());

$router = new RouteMatcher($collection);

$router->addValidationFunction('json', function ($value) {
	$parts = explode('.', $value);
	if (count($parts) === 2) {
		if ($parts[1] === 'json' && ctype_alnum($parts[0])) {
			return true;
		}
	}
	return false;
});


$iterationCount = 1000;

$start = microtime(true);

for ($i = 0; $i < $iterationCount; $i++) {

	// $result = $router->match('GET', '/api/collection/post/55');
	// $result = $router->match('GET', 'my.domain.de/admin/login/hhhsdfsdf/hamer.json');
	$result = $router->match('GET', '/category/post/55');
}

$end = microtime(true) - $start;

var_dump($end, $result);
