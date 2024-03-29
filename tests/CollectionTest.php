<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Routing\Collection\RouteCollection;
use Semperton\Routing\Matcher\RouteMatcher;
use Semperton\Routing\Tests\DummyCollection;

final class CollectionTest extends TestCase
{
	public function testMethodHelpers(): void
	{
		$routes = new DummyCollection();

		$routes->map(['GET', 'post', 'PUT', 'PATCH', 'delete', 'OPTIONS'], '/all', 'all-handler');
		$routes
			->get('/get', 'get-handler')
			->post('/post', 'post-handler')
			->put('/put', 'put-handler')
			->patch('/patch', 'patch-handler')
			->delete('/delete', 'delete-handler')
			->options('/options', 'options-handler');

		$expected = [
			['GET', '/all', 'all-handler', ''],
			['POST', '/all', 'all-handler', ''],
			['PUT', '/all', 'all-handler', ''],
			['PATCH', '/all', 'all-handler', ''],
			['DELETE', '/all', 'all-handler', ''],
			['OPTIONS', '/all', 'all-handler', ''],

			['GET', '/get', 'get-handler', ''],
			['POST', '/post', 'post-handler', ''],
			['PUT', '/put', 'put-handler', ''],
			['PATCH', '/patch', 'patch-handler', ''],
			['DELETE', '/delete', 'delete-handler', ''],
			['OPTIONS', '/options', 'options-handler', '']
		];

		$this->assertSame($expected, $routes->routes);
	}

	public function testRouteGroups(): void
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

	public function testNamedRoutes(): void
	{
		$routes = new DummyCollection();

		$routes->get('/category/:slug', 'category-handler', 'category-route');
		$routes->post('blog/', 'blog-handler', 'blog-route');

		$expected = [
			['GET', '/category/:slug', 'category-handler', 'category-route'],
			['POST', 'blog/', 'blog-handler', 'blog-route'],
		];

		$this->assertSame($expected, $routes->routes);

		$route = $routes->reverse('category-route', ['slug' => 'new']);
		$this->assertEquals('/category/new', $route);
	}

	public function testCloneCollection(): void
	{
		$collection = new RouteCollection();
		$collection2 = clone $collection;

		$collection2->get('/blog', 'blog-route');

		$matcher = new RouteMatcher($collection);
		$result = $matcher->match('GET', '/blog');

		$this->assertFalse($result->isMatch());
	}

	public function testRouteData(): void
	{
		$collection = new RouteCollection();

		$collection->get('/blog', 'blog-handler', 'blog-route');
		$collection->post('/update', 'update-handler');

		$filename = __DIR__ . '/routes.php';

		$dump = $collection->dump();

		file_put_contents($filename, '<?php return ' . $dump . ';');

		$data = require $filename;

		$this->assertIsArray($data);

		$newCollection = RouteCollection::fromArray($data);

		$this->assertInstanceOf(RouteCollection::class, $newCollection);

		unlink($filename);
	}
}
