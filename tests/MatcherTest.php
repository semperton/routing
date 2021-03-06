<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Routing\MatchResult;
use Semperton\Routing\RouteCollection;
use Semperton\Routing\RouteMatcher;

require_once __DIR__ . '/../vendor/autoload.php';

final class MatcherTest extends TestCase
{
	public function testMatchResult()
	{
		$routes = new RouteCollection();
		$routes->post('/category/:category:w/:id:d', 'post-handler');
		$routes->delete('/category/:category:w/:id:d', 'delete-handler');

		$matcher = new RouteMatcher($routes->routeTree());
		$result = $matcher->match('POST', '/category/new/42');

		$this->assertInstanceOf(MatchResult::class, $result);
		$this->assertTrue($result->isMatch());
		$this->assertSame(['category' => 'new', 'id' => '42'], $result->getParams());

		$result = $matcher->match('GET', '/category/new/42');

		// if we match with the wrong http method,
		// isMatch is false and getMethods must contain the allowed methods
		$this->assertFalse($result->isMatch());
		$this->assertSame(['DELETE', 'POST'], $result->getMethods());
	}

	public function testSetValidator()
	{
		$routes = new RouteCollection();
		$routes->get('/validate/:slug:n', 'new-handler');
		$routes->get('/validate/:slug:w', 'default-handler');

		$matcher = new RouteMatcher($routes->routeTree());
		$matcher->setValidator('n', function (string $val) {
			return strpos($val, 'new-') === 0;
		});

		$result1 = $matcher->match('GET', '/validate/product');
		$result2 = $matcher->match('GET', '/validate/new-product');

		$this->assertEquals('default-handler', $result1->getHandler());
		$this->assertEquals('new-handler', $result2->getHandler());
	}

	public function testInvalidValidator()
	{
		$this->expectException(InvalidArgumentException::class);
		$routes = new RouteCollection();
		$routes->get('/foo/:bar:k', 'handler');

		$matcher = new RouteMatcher($routes->routeTree());
		$matcher->match('GET', '/foo/bar');
	}

	public function testTrailingSlash()
	{
		$routes = new RouteCollection();
		$routes->get('/slash', 'slash-handler');

		$matcher = new RouteMatcher($routes->routeTree());
		$result1 = $matcher->match('GET', '/slash');
		$result2 = $matcher->match('GET', '/slash/');

		$this->assertEquals($result1->isMatch(), $result2->isMatch());
		$this->assertEquals($result1->getHandler(), $result2->getHandler());
	}

	public function testMethodNotAllowed()
	{
		$routes = new RouteCollection();
		$routes->get('/product/:id:d', 'get-handler');
		$routes->post('/product/:number:d', 'post-handler');

		$matcher = new RouteMatcher($routes->routeTree());
		$result = $matcher->match('DELETE', '/product/42');

		$this->assertFalse($result->isMatch());
		$this->assertSame(['GET', 'POST'], $result->getMethods());
	}
}
