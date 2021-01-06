<?php

declare(strict_types=1);

use Semperton\Routing\RouteCollection;
use Semperton\Routing\RouteMatcher;

require_once __DIR__ . '/../vendor/autoload.php';

$collection = new RouteCollection();
$router = new RouteMatcher($collection);

$collection->get('/admin/*remain:d', 'handler-admin');
// $collection->get('/blog', 'handler-blog');
// $collection->get('/blog/:post_slug:w', 'handler-slug');
// $collection->get('/category/:id:d', 'handler-id');

$collection->get('/api/collection/:collection:w/:id:d', 'collection-handler');

$start = microtime(true);

// $result = $router->match('GET', '/api/collection/post/55');
$result = $router->match('GET', '/admin/55/edit');

$end = microtime(true) - $start;

var_dump($end, $result);
