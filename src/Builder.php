<?php

declare(strict_types=1);

namespace Semperton\Routing;

use InvalidArgumentException;

class Builder
{
	public function buildRoute(Route $route, array $params): string
	{
		$path = $route->path;

		$trailingSlash = $path[-1] === '/';

		$tokens = explode('/', trim($path, '/'));

		foreach ($tokens as $i => &$token) {

			$first = $token[0];
			if ($first === ':' || $first === '*') {

				$split = explode(':', substr($token, 1));
				if (!isset($params[$split[0]])) {
					throw new InvalidArgumentException("No value defined for placeholder < $split[0] >");
				}

				$token = $params[$split[0]];

				if ($first === '*') {
					$tokens = array_slice($tokens, 0, $i + 1);
					break;
				}
			}
		}

		$path = '/' . implode('/', $tokens);

		if ($trailingSlash) {
			$path .= '/';
		}

		return $path;
	}

	/** @param Route[] $routes */
	public function buildTree(array $routes): TreeNode
	{
		$tree = new TreeNode();

		foreach ($routes as $route) {

			$tokens = explode('/', trim($route->path, '/'));
			$this->map($tree, $tokens, $route->method, $route->handler);
		}

		return $tree;
	}

	protected function map(TreeNode $node, array $tokens, string $method, $handler): void
	{
		foreach ($tokens as $token) {

			if ($token === '') { // index
				break;
			}

			$first = $token[0];

			if ($first === '*') { // catchall
				$token = substr($token, 1);
				$node->catchall[$token] = true;
				break;
			}

			$key = 'static';

			if ($first === ':') { // placeholder
				$key = 'placeholder';
				$token = substr($token, 1);
			}

			$path = &$node->{$key};

			if (!isset($path[$token])) {
				$path[$token] = new TreeNode();
			}

			$node = $path[$token];
		}

		$node->leaf = true;
		$node->handler[$method] = $handler;
	}
}
