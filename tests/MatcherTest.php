<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Semperton\Routing\MatchResult;
use Semperton\Routing\Collection\RouteCollection;
use Semperton\Routing\Matcher\RouteMatcher;

final class MatcherTest extends TestCase
{
	public function testMatchIndex(): void
	{
		$routes = new RouteCollection();
		$routes->get('/', 'index-handler');

		$matcher = new RouteMatcher($routes);
		$result = $matcher->match('GET', '/');

		$this->assertTrue($result->isMatch());
		$this->assertEquals('index-handler', $result->getHandler());
	}

	public function testMatchResult(): void
	{
		$routes = new RouteCollection();
		$routes->post('/category/:category:w/:id:d', 'post-handler');
		$routes->delete('/category/:category:w/:id:d', 'delete-handler');

		$matcher = new RouteMatcher($routes);
		$result = $matcher->match('POST', '/category/new/42');

		$this->assertInstanceOf(MatchResult::class, $result);
		$this->assertTrue($result->isMatch());
		$this->assertSame(['category' => 'new', 'id' => '42'], $result->getParams());

		$result = $matcher->match('POST', '/category/new-/42');
		$this->assertFalse($result->isMatch());

		$result = $matcher->match('GET', '/category/new/42');

		// if we match with the wrong http method,
		// isMatch is false and getMethods must contain the allowed methods
		$this->assertFalse($result->isMatch());
		$this->assertSame(['DELETE', 'POST'], $result->getMethods());
	}

	public function testMatchRequest(): void
	{
		$routes = new RouteCollection();
		$routes->post('/user/:id:d', 'user-handler');

		$uri = $this->createStub(UriInterface::class);
		$uri->method('getPath')->willReturn('/user/7');

		$request = $this->createStub(ServerRequestInterface::class);
		$request->method('getMethod')->willReturn('POST');
		$request->method('getUri')->willReturn($uri);

		$result = (new RouteMatcher($routes))->matchRequest($request);

		$this->assertSame('user-handler', $result->getHandler());
		$this->assertSame(['id' => '7'], $result->getParams());
	}

	public function testSetValidator(): void
	{
		$routes = new RouteCollection();
		$routes->setValidator('n', fn (string $val): bool => str_starts_with($val, 'new-'));
		$routes->get('/validate/:slug:n', 'new-handler');
		$routes->get('/validate/:slug:w', 'default-handler');

		$matcher = new RouteMatcher($routes);

		$result1 = $matcher->match('GET', '/validate/product');
		$result2 = $matcher->match('GET', '/validate/new-product');

		$this->assertEquals('default-handler', $result1->getHandler());
		$this->assertEquals('new-handler', $result2->getHandler());
	}

	public function testEmptySegment(): void
	{
		$routes = new RouteCollection();
		$routes->get('/user/:name', 'user-handler');
		$routes->get('/files/*path', 'files-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertFalse($matcher->match('GET', '/user/')->isMatch());
		$this->assertSame(['path' => ''], $matcher->match('GET', '/files/')->getParams());
	}

	public function testMethodIsCaseSensitive(): void
	{
		$routes = new RouteCollection();
		$routes->map(['PURGE'], '/cache', 'purge-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertTrue($matcher->match('PURGE', '/cache')->isMatch());
		$this->assertFalse($matcher->match('purge', '/cache')->isMatch());
	}

	public function testEmptyRequestPath(): void
	{
		$routes = new RouteCollection();
		$routes->get('/', 'index-handler');

		$uri = $this->createStub(UriInterface::class);
		$uri->method('getPath')->willReturn('');

		$request = $this->createStub(ServerRequestInterface::class);
		$request->method('getMethod')->willReturn('GET');
		$request->method('getUri')->willReturn($uri);

		$this->assertSame('index-handler', (new RouteMatcher($routes))->matchRequest($request)->getHandler());
	}

	public function testTrailingSlash(): void
	{
		$routes = new RouteCollection();
		$routes->get('/slash', 'slash-handler');

		$matcher = new RouteMatcher($routes);
		$result1 = $matcher->match('GET', '/slash');
		$result2 = $matcher->match('GET', '/slash/');

		$this->assertNotEquals($result1->isMatch(), $result2->isMatch());
		$this->assertNotEquals($result1->getHandler(), $result2->getHandler());
	}

	public function testMethodNotAllowed(): void
	{
		$routes = new RouteCollection();
		$routes->get('/product/:id:d', 'get-handler');
		$routes->post('/product/:number:d', 'post-handler');

		$matcher = new RouteMatcher($routes);
		$result = $matcher->match('DELETE', '/product/42');

		$this->assertFalse($result->isMatch());
		$this->assertSame(['GET', 'POST'], $result->getMethods());
	}

	public function testWildcard(): void
	{
		$routes = new RouteCollection();
		$routes->get('/*path', 'get-handler');

		$matcher = new RouteMatcher($routes);
		$result = $matcher->match('GET', '/admin/users/');

		$this->assertSame(['path' => 'admin/users/'], $result->getParams());
	}

	public function testDecoding(): void
	{
		$routes = new RouteCollection();
		$routes->get('/über/:name', 'name-handler');
		$routes->get('/über/:a/:b', 'split-handler');
		$routes->get('/files/*path', 'files-handler');

		$matcher = new RouteMatcher($routes);

		$result = $matcher->match('GET', '/%C3%BCber/hello%20world');
		$this->assertSame('name-handler', $result->getHandler());
		$this->assertSame(['name' => 'hello world'], $result->getParams());

		// an encoded slash stays part of its segment
		$result = $matcher->match('GET', '/%C3%BCber/a%2Fb');
		$this->assertSame('name-handler', $result->getHandler());
		$this->assertSame(['name' => 'a/b'], $result->getParams());

		$result = $matcher->match('GET', '/files/a%20b/c');
		$this->assertSame(['path' => 'a b/c'], $result->getParams());
	}

	public function testValidatorReceivesDecodedValue(): void
	{
		$routes = new RouteCollection();
		$routes->setValidator('safe', fn (string $value): bool => $value !== '..');
		$routes->get('/file/:name:safe', 'file-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertTrue($matcher->match('GET', '/file/a.txt')->isMatch());
		$this->assertFalse($matcher->match('GET', '/file/..')->isMatch());
		$this->assertFalse($matcher->match('GET', '/file/%2E%2E')->isMatch());
	}

	public function testWildcardDoesNotShadowParent(): void
	{
		$routes = new RouteCollection();
		$routes->get('/blog', 'blog-handler');
		$routes->get('/blog/*rest', 'rest-handler');

		$matcher = new RouteMatcher($routes);

		$result = $matcher->match('GET', '/blog');
		$this->assertSame('blog-handler', $result->getHandler());
		$this->assertSame([], $result->getParams());

		$result = $matcher->match('GET', '/blog/a/b');
		$this->assertSame('rest-handler', $result->getHandler());
		$this->assertSame(['rest' => 'a/b'], $result->getParams());
	}

	public function testWildcardWithoutParentRoute(): void
	{
		$routes = new RouteCollection();
		$routes->get('/files/*path', 'files-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertFalse($matcher->match('GET', '/files')->isMatch());
	}

	public function testWildcardValidatesWholeRest(): void
	{
		$routes = new RouteCollection();
		$routes->get('/f/*path:d', 'digit-handler');
		$routes->get('/f/*any', 'any-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertSame('digit-handler', $matcher->match('GET', '/f/12')->getHandler());
		$this->assertSame('any-handler', $matcher->match('GET', '/f/12/abc')->getHandler());
	}

	public function testWildcardMethodNotAllowed(): void
	{
		$routes = new RouteCollection();
		$routes->get('/files/*path', 'files-handler');

		$matcher = new RouteMatcher($routes);
		$result = $matcher->match('POST', '/files/a');

		$this->assertFalse($result->isMatch());
		$this->assertSame(['GET'], $result->getMethods());
	}

	public function testBacktracking(): void
	{
		$routes = new RouteCollection();
		$routes->get('/blog/recent', 'recent-handler');
		$routes->get('/blog/:slug/comments', 'comments-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertSame('recent-handler', $matcher->match('GET', '/blog/recent')->getHandler());

		$result = $matcher->match('GET', '/blog/recent/comments');
		$this->assertSame('comments-handler', $result->getHandler());
		$this->assertSame(['slug' => 'recent'], $result->getParams());
	}

	public function testStaticMethodMismatchFallsThrough(): void
	{
		$routes = new RouteCollection();
		$routes->get('/item/new', 'new-handler');
		$routes->post('/item/:id', 'post-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertSame('new-handler', $matcher->match('GET', '/item/new')->getHandler());

		$result = $matcher->match('POST', '/item/new');
		$this->assertSame('post-handler', $result->getHandler());
		$this->assertSame(['id' => 'new'], $result->getParams());

		$result = $matcher->match('PUT', '/item/new');
		$this->assertSame(['GET', 'POST'], $result->getMethods());
	}

	public function testPrecedence(): void
	{
		$routes = new RouteCollection();
		$routes->get('/*path', 'catchall-handler');
		$routes->get('/:slug', 'placeholder-handler');
		$routes->get('/about', 'static-handler');

		$matcher = new RouteMatcher($routes);

		$this->assertSame('static-handler', $matcher->match('GET', '/about')->getHandler());
		$this->assertSame('placeholder-handler', $matcher->match('GET', '/contact')->getHandler());
		$this->assertSame('catchall-handler', $matcher->match('GET', '/a/b')->getHandler());
	}

	public function testMethodNotAllowedIsList(): void
	{
		$routes = new RouteCollection();
		$routes->get('/item/:id:d', 'a');
		$routes->post('/item/:num:d', 'b');
		$routes->get('/item/:any', 'c');

		$matcher = new RouteMatcher($routes);

		$this->assertSame(['GET', 'POST'], $matcher->match('DELETE', '/item/1')->getMethods());
	}

	public function testHead(): void
	{
		$routes = new RouteCollection();
		$routes->get('/a', 'get-a');
		$routes->get('/b', 'get-b');
		$routes->map(['HEAD'], '/b', 'head-b');
		$routes->post('/c', 'post-c');

		$matcher = new RouteMatcher($routes);

		$this->assertSame('get-a', $matcher->match('HEAD', '/a')->getHandler());
		$this->assertSame('head-b', $matcher->match('HEAD', '/b')->getHandler());
		$this->assertSame('get-b', $matcher->match('GET', '/b')->getHandler());
		$this->assertFalse($matcher->match('HEAD', '/c')->isMatch());
	}

	public function testBasePath(): void
	{
		$routes = new RouteCollection();
		$routes->group('/api', function (RouteCollection $api): void {
			$api->get('', 'root-handler');
			$api->get('/v2/:id:d', 'x-handler', 'x');
		});

		$matcher = new RouteMatcher($routes);

		$this->assertSame('/api/v2/7', $routes->reverse('x', ['id' => 7]));
		$this->assertSame('root-handler', $matcher->match('GET', '/api')->getHandler());
		$this->assertSame('x-handler', $matcher->match('GET', '/api/v2/7')->getHandler());
		$this->assertFalse($matcher->match('GET', '/apiv2/7')->isMatch());
		$this->assertFalse($matcher->match('GET', '/v2/7')->isMatch());
	}

	public function testDoubleSlashRequest(): void
	{
		$routes = new RouteCollection();
		$routes->get('/a/:id/b', 'handler');

		$matcher = new RouteMatcher($routes);
		$this->assertFalse($matcher->match('GET', '/a//b')->isMatch());
		$this->assertFalse($matcher->match('GET', '//a/1/b')->isMatch());
	}

	public function testRoutesAddedAfterMatcherCreation(): void
	{
		$routes = new RouteCollection();
		$matcher = new RouteMatcher($routes);

		$routes->get('/late', 'late-handler');

		$this->assertTrue($matcher->match('GET', '/late')->isMatch());
	}
}
