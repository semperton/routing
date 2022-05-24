<?php

declare(strict_types=1);

use Semperton\Routing\Collection\RouteCollection;
use Semperton\Routing\Matcher\RouteMatcher;

final class MatcherBench
{
	protected $matcher;

	public function __construct()
	{
		$routes = new RouteCollection();
		$routes->map(['GET'], 			'/', 1);
		$routes->map(['GET'], 			'/page/:page:w', 2);
		$routes->map(['GET'],			'/about-us', 3);
		$routes->map(['GET'],			'/contact-us', 4);
		$routes->map(['POST'],			'/contact-us', 5);
		$routes->map(['GET'],			'/blog', 6);
		$routes->map(['GET'],			'/blog/recent', 7);
		$routes->map(['GET'],			'/blog/post/:post:w', 8);
		$routes->map(['POST'],			'/blog/post/:post:w/comment', 9);
		$routes->map(['GET'],			'/shop', 10);
		$routes->map(['GET'],			'/shop/category', 11);
		$routes->map(['GET'],			'/shop/category/search/:filter:a', 12);
		$routes->map(['GET'],			'/shop/category/:category:d', 13);
		$routes->map(['GET'],			'/shop/category/:category:d/product', 14);
		$routes->map(['GET'],			'/shop/category/:category:d/product/search/:filter:a', 15);
		$routes->map(['GET'],			'/shop/product', 16);
		$routes->map(['GET'],			'/shop/product/search/:filter:a', 17);
		$routes->map(['GET'],			'/shop/product/:product:d', 18);
		$routes->map(['GET'],			'/shop/cart', 19);
		$routes->map(['PUT'],			'/shop/cart', 20);
		$routes->map(['DELETE'], 		'/shop/cart', 21);
		$routes->map(['GET'],			'/shop/cart/checkout', 22);
		$routes->map(['POST'],			'/shop/cart/checkout', 23);
		$routes->map(['GET'],			'/admin/login', 24);
		$routes->map(['POST'],			'/admin/login', 25);
		$routes->map(['GET'],			'/admin/logout', 26);
		$routes->map(['GET'],			'/admin', 27);
		$routes->map(['GET'],			'/admin/product', 28);
		$routes->map(['GET'],			'/admin/product/create', 29);
		$routes->map(['POST'],			'/admin/product', 30);
		$routes->map(['GET'],			'/admin/product/:product:d', 31);
		$routes->map(['GET'],			'/admin/product/:product:d/edit', 32);
		$routes->map(['PUT', 'PATCH'], 	'/admin/product/:product:d', 33);
		$routes->map(['DELETE'],		'/admin/product/:product:d', 34);
		$routes->map(['GET'],			'/admin/category', 35);
		$routes->map(['GET'],			'/admin/category/create', 36);
		$routes->map(['POST'],			'/admin/category', 37);
		$routes->map(['GET'],			'/admin/category/:category:d', 38);
		$routes->map(['GET'],			'/admin/category/:category:d/edit', 39);
		$routes->map(['PUT', 'PATCH'],	'/admin/category/:category:d', 40);

		$this->matcher = new RouteMatcher($routes);
	}

	/**
	 * @Warmup(10)
	 * @Revs(10000)
	 * @Iterations(10)
	 * @OutputTimeUnit("seconds")
	 * @OutputMode("throughput")
	 */
	public function benchBasicRouting()
	{
		$result = $this->matcher->match('GET', '/admin/category/55');
	}
}
