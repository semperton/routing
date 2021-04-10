<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Routing\RouteCollection;
use Semperton\Routing\Tests\DummyCollection;

final class CollectionTest extends TestCase
{
	public function testMethodHelpers()
	{
		$routes = new DummyCollection();

		$routes->map(['GET', 'post', 'PUT', 'PATCH', 'delete', 'OPTIONS', 'HEAD'], '/all', 'all-handler');
		$routes
			->get('/get', 'get-handler')
			->post('/post', 'post-handler')
			->put('/put', 'put-handler')
			->patch('/patch', 'patch-handler')
			->delete('/delete', 'delete-handler')
			->options('/options', 'options-handler')
			->head('/head', 'head-handler');

		$expected = [
			['GET', '/all', 'all-handler', ''],
			['POST', '/all', 'all-handler', ''],
			['PUT', '/all', 'all-handler', ''],
			['PATCH', '/all', 'all-handler', ''],
			['DELETE', '/all', 'all-handler', ''],
			['OPTIONS', '/all', 'all-handler', ''],
			['HEAD', '/all', 'all-handler', ''],

			['GET', '/get', 'get-handler', ''],
			['POST', '/post', 'post-handler', ''],
			['PUT', '/put', 'put-handler', ''],
			['PATCH', '/patch', 'patch-handler', ''],
			['DELETE', '/delete', 'delete-handler', ''],
			['OPTIONS', '/options', 'options-handler', ''],
			['HEAD', '/head', 'head-handler', ''],
		];

		$this->assertSame($expected, $routes->routes);
	}

	public function testRouteGroups()
	{
		$routes = new DummyCollection();

		$routes->group('/blog', function (RouteCollection $blog) {
			$blog->get('/:slug:w', 'slug-handler');
			$blog->put('/:id:d', 'id-handler');
		});

		$routes->group('/user-', function (RouteCollection $user) {
			$user->get('login', 'login-handler');
		});

		$routes->group('/user', function (RouteCollection $user) {
			$user->post('-logout', 'logout-handler');
		});

		$expected = [
			['GET', '/blog/:slug:w', 'slug-handler', ''],
			['PUT', '/blog/:id:d', 'id-handler', ''],
			['GET', '/user-login', 'login-handler', ''],
			['POST', '/user-logout', 'logout-handler', '']
		];

		$this->assertSame($expected, $routes->routes);
	}

	public function testNamedRoutes()
	{
		$routes = new DummyCollection();

		$routes->get('/category/:slug', 'category-handler', 'category-route');
		$routes->post('/blog/', 'blog-handler', 'blog-route');

		$expected = [
			['GET', '/category/:slug', 'category-handler', 'category-route'],
			['POST', '/blog/', 'blog-handler', 'blog-route'],
		];

		$this->assertSame($expected, $routes->routes);

		$route = $routes->reverse('category-route', ['slug' => 'new']);
		$this->assertEquals('/category/new', $route);
	}

	// public function testRouteTree()
	// {
	// 	$routes = new RouteCollection();

	// 	$routes->get('/admin/*remain', 'admin-handler');
	// 	$routes->get('/blog', 'blog-handler');
	// 	$routes->get('/blog/:post_slug:w', 'post-handler');
	// 	$routes->delete('/category/:name:w/:id:d', 'category-handler');;
	// }
}
