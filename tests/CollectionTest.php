<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Routing\RouteCollection;

require_once __DIR__ . '/../vendor/autoload.php';

final class CollectionTest extends TestCase
{
	public function testMethodHelpers()
	{
		$routes = new RouteCollection();

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
			['GET', '/all', 'all-handler'],
			['POST', '/all', 'all-handler'],
			['PUT', '/all', 'all-handler'],
			['PATCH', '/all', 'all-handler'],
			['DELETE', '/all', 'all-handler'],
			['OPTIONS', '/all', 'all-handler'],
			['HEAD', '/all', 'all-handler'],

			['GET', '/get', 'get-handler'],
			['POST', '/post', 'post-handler'],
			['PUT', '/put', 'put-handler'],
			['PATCH', '/patch', 'patch-handler'],
			['DELETE', '/delete', 'delete-handler'],
			['OPTIONS', '/options', 'options-handler'],
			['HEAD', '/head', 'head-handler'],
		];

		$this->assertSame($expected, $routes->toArray());
	}

	public function testRouteGroups()
	{
		$routes = new RouteCollection();

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
			['GET', '/blog/:slug:w', 'slug-handler'],
			['PUT', '/blog/:id:d', 'id-handler'],
			['GET', '/user-login', 'login-handler'],
			['POST', '/user-logout', 'logout-handler']
		];

		$this->assertSame($expected, $routes->toArray());
	}

	public function testRouteTree()
	{
		$routes = new RouteCollection();

		$routes->get('/admin/*remain', 'admin-handler');
		$routes->get('/blog', 'blog-handler');
		$routes->get('/blog/:post_slug:w', 'post-handler');
		$routes->delete('/category/:name:w/:id:d', 'category-handler');

		$tree = $routes->toTree();
		echo var_export($tree, true);
		die();

		// $expected = [
		// 	RC::NODE_STATIC => [
		// 		'admin' => [
		// 			RC::NODE_CATCHALL => [
		// 				'remain' => true
		// 			],
		// 			RC::NODE_LEAF => true,
		// 			RC::NODE_HANDLER => ['GET' => 'admin-handler']
		// 		],
		// 		'blog' => [
		// 			RC::NODE_LEAF => true,
		// 			RC::NODE_HANDLER => ['GET' => 'blog-handler'],
		// 			RC::NODE_PLACEHOLDER => [
		// 				'post_slug:w' => [
		// 					RC::NODE_LEAF => true,
		// 					RC::NODE_HANDLER => ['GET' => 'post-handler']
		// 				]
		// 			]
		// 		],
		// 		'category' => [
		// 			RC::NODE_PLACEHOLDER => [
		// 				'name:w' => [
		// 					RC::NODE_PLACEHOLDER => [
		// 						'id:d' => [
		// 							RC::NODE_LEAF => true,
		// 							RC::NODE_HANDLER => ['DELETE' => 'category-handler'],
		// 						]
		// 					]
		// 				]
		// 			]
		// 		]
		// 	]
		// ];

		// $this->assertSame($expected, $routes->toTree());
	}
}
