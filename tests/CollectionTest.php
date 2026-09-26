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

		$routes->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/all', 'all-handler');
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
		$routes->post('/blog/', 'blog-handler', 'blog-route');

		$expected = [
			['GET', '/category/:slug', 'category-handler', 'category-route'],
			['POST', '/blog/', 'blog-handler', 'blog-route'],
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

	public function testCloneIsIndependent(): void
	{
		$collection = new RouteCollection();
		$collection->get('/blog', 'blog-handler');
		$collection->get('/post/:id:d', 'post-handler');

		$clone = clone $collection;
		$clone->post('/blog', 'blog-post-handler');
		$clone->post('/post/:id:d', 'post-post-handler');

		$matcher = new RouteMatcher($collection);
		$this->assertSame(['GET'], $matcher->match('POST', '/blog')->getMethods());
		$this->assertSame(['GET'], $matcher->match('POST', '/post/1')->getMethods());

		$matcher = new RouteMatcher($clone);
		$this->assertSame('blog-post-handler', $matcher->match('POST', '/blog')->getHandler());
		$this->assertSame('post-post-handler', $matcher->match('POST', '/post/1')->getHandler());
	}

	public function testReverseEncoding(): void
	{
		$routes = new RouteCollection();
		$routes->get('/tag/:name', 'tag-handler', 'tag');
		$routes->get('/files/*path', 'files-handler', 'files');

		$this->assertSame('/tag/a%2Fb%20c', $routes->reverse('tag', ['name' => 'a/b c']));
		$this->assertSame('/files/a%20b/c', $routes->reverse('files', ['path' => 'a b/c']));
	}

	public function testReverseRoundTrip(): void
	{
		$routes = new RouteCollection();
		$routes->get('/über/:name', 'name-handler', 'name');

		$path = $routes->reverse('name', ['name' => 'a/b c']);
		$this->assertSame('/%C3%BCber/a%2Fb%20c', $path);

		$result = (new RouteMatcher($routes))->match('GET', $path);
		$this->assertSame(['name' => 'a/b c'], $result->getParams());
		$this->assertSame($path, $routes->reverse('name', $result->getParams()));
	}

	public function testReverseMissingParam(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$routes = new RouteCollection();
		$routes->get('/tag/:name', 'tag-handler', 'tag');
		$routes->reverse('tag');
	}

	public function testReverseUnknownRoute(): void
	{
		$this->expectException(OutOfBoundsException::class);

		(new RouteCollection())->reverse('unknown');
	}

	public function testDuplicateName(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$routes = new RouteCollection();
		$routes->get('/a', 'a-handler', 'route');
		$routes->get('/b', 'b-handler', 'route');
	}

	public function testDuplicateRoute(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$routes = new RouteCollection();
		$routes->map(['GET', 'POST'], '/a', 'a-handler');
		$routes->post('/a', 'b-handler');
	}

	public function testSameRouteDifferentMethods(): void
	{
		$routes = new RouteCollection();
		$routes->get('/a', 'get-handler');
		$routes->post('/a', 'post-handler');

		$matcher = new RouteMatcher($routes);
		$this->assertSame('post-handler', $matcher->match('POST', '/a')->getHandler());
	}

	public function testLeadingSlash(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new RouteCollection())->get('blog', 'handler');
	}

	public function testEmptySegment(): void
	{
		$routes = new RouteCollection();
		$routes->get('/trailing/', 'trailing-handler');

		$this->expectException(InvalidArgumentException::class);

		$routes->group('/api/', function (RouteCollection $api): void {
			$api->get('/users', 'users-handler');
		});
	}

	public function testDuplicateParamName(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new RouteCollection())->get('/a/:id/b/:id', 'handler');
	}

	public function testUnknownValidator(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new RouteCollection())->get('/foo/:bar:k', 'handler');
	}

	public function testValidatorCannotBeReplaced(): void
	{
		$routes = new RouteCollection();
		$routes->setValidator('n', fn (string $v): bool => true);

		foreach (['d', 'n', ''] as $id) {
			try {
				$routes->setValidator($id, fn (string $v): bool => true);
				$this->fail("Validator < $id > was replaced");
			} catch (InvalidArgumentException) {
			}
		}

		$this->assertFalse($routes->validate('abc', 'd'));
	}

	public function testValidatorMustReturnBool(): void
	{
		$this->expectException(TypeError::class);

		$routes = new RouteCollection();
		$routes->setValidator('n', fn (string $v) => 1);
		$routes->validate('x', 'n');
	}

	public function testWordValidator(): void
	{
		$routes = new RouteCollection();

		$this->assertTrue($routes->validate('foo_Bar9', 'w'));
		$this->assertTrue($routes->validate('___', 'w'));
		$this->assertFalse($routes->validate('', 'w'));
		$this->assertFalse($routes->validate('foo-bar', 'w'));
		$this->assertFalse($routes->validate('café', 'w'));
	}

	public function testBuiltinValidators(): void
	{
		$routes = new RouteCollection();

		$valid = ['A' => 'aZ09', 'a' => 'aZ', 'd' => '09', 'x' => '09afAF', 'l' => 'az', 'u' => 'AZ', 'w' => 'a_Z9'];
		$invalid = ['A' => 'a_', 'a' => 'a1', 'd' => '1a', 'x' => 'g', 'l' => 'aZ', 'u' => 'Az', 'w' => 'a-b'];

		foreach ($valid as $id => $value) {
			$this->assertTrue($routes->validate($value, $id), "< $value > should pass < $id >");
			$this->assertFalse($routes->validate($invalid[$id], $id), "< {$invalid[$id]} > should fail < $id >");
			$this->assertFalse($routes->validate('', $id), "empty value should fail < $id >");
			$this->assertFalse($routes->validate("\xE9", $id), "non-ASCII should fail < $id >");
		}
	}

	public function testReverseValidatesValues(): void
	{
		$routes = new RouteCollection();
		$routes->get('/p/:id:d', 'p-handler', 'p');
		$routes->get('/t/:tag', 't-handler', 't');

		$this->assertSame('/p/42', $routes->reverse('p', ['id' => 42]));

		foreach ([['p', ['id' => 'abc']], ['t', ['tag' => '']]] as [$name, $params]) {
			try {
				$routes->reverse($name, $params);
				$this->fail("Reverse of < $name > accepted an invalid value");
			} catch (InvalidArgumentException) {
			}
		}
	}

	public function testEmptyMethods(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new RouteCollection())->map([], '/a', 'handler');
	}

	public function testCatchallMustBeLast(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new RouteCollection())->get('/files/*path/edit', 'handler');
	}

	public function testMissingParamName(): void
	{
		$this->expectException(InvalidArgumentException::class);

		(new RouteCollection())->get('/files/:', 'handler');
	}

	public function testGroupRestoresPrefixOnException(): void
	{
		$routes = new RouteCollection();

		try {
			$routes->group('/admin', function (): void {
				throw new RuntimeException();
			});
		} catch (RuntimeException) {
		}

		$routes->get('/login', 'login-handler');

		$matcher = new RouteMatcher($routes);
		$this->assertTrue($matcher->match('GET', '/login')->isMatch());
	}
}
