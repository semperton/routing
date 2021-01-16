<?php

declare(strict_types=1);

namespace Semperton\Routing;

use Closure;

class RouteCollection
{
	const NODE_LEAF = 0;
	const NODE_HANDLER = 1;
	const NODE_STATIC = 2;
	const NODE_PLACEHOLDER = 3;
	const NODE_CATCHALL = 4;

	/** @var string */
	protected $prefix = '';

	/** @var array */
	protected $tree;

	public function __construct(array $tree = [])
	{
		$this->tree = $tree;
	}

	public function getTree(): array
	{
		return $this->tree;
	}

	public function map(array $methods, string $path, $target): self
	{
		$handler = [];
		foreach ($methods as $method) {
			$method = strtoupper($method);
			$handler[$method] = $target;
		}

		$path = $this->prefix . $path;
		$path = trim($path, '/');

		$tokens = explode('/', $path);

		$this->buildTree($this->tree, $tokens, $handler);

		return $this;
	}

	protected function buildTree(array &$node, array $tokens, array $handler): void
	{
		foreach ($tokens as $token) {

			if ($token === '') { // index
				break;
			}

			if ($token[0] === '*') { // catchall

				if (!isset($node[self::NODE_CATCHALL])) {
					$node[self::NODE_CATCHALL] = [];
				}
				$token = substr($token, 1);
				$node[self::NODE_CATCHALL][$token] = true;
				break;
			}

			$switch = self::NODE_STATIC;

			if ($token[0] === ':') { // placeholder
				$switch = self::NODE_PLACEHOLDER;
				$token = substr($token, 1);
			}

			if (!isset($node[$switch])) {
				$node[$switch] = [];
			}

			$treePath = &$node[$switch];

			if (!isset($treePath[$token])) {
				$treePath[$token] = []; // new node
			}

			$node = &$treePath[$token];
		}

		$node[self::NODE_LEAF] = true;

		if (isset($node[self::NODE_HANDLER])) {
			$node[self::NODE_HANDLER] = $handler + $node[self::NODE_HANDLER];
		} else {
			$node[self::NODE_HANDLER] = $handler;
		}
	}

	public function group(string $path, Closure $callback): self
	{
		$currentPrefix = $this->prefix;

		$this->prefix .= $path;

		$callback($this);

		$this->prefix = $currentPrefix;

		return $this;
	}

	public function get(string $path, $handler): self
	{
		return $this->map(['GET'], $path, $handler);
	}

	public function post(string $path, $handler): self
	{
		return $this->map(['POST'], $path, $handler);
	}

	public function put(string $path, $handler): self
	{
		return $this->map(['PUT'], $path, $handler);
	}

	public function delete(string $path, $handler): self
	{
		return $this->map(['DELETE'], $path, $handler);
	}

	public function patch(string $path, $handler): self
	{
		return $this->map(['PATCH'], $path, $handler);
	}

	public function head(string $path, $handler): self
	{
		return $this->map(['HEAD'], $path, $handler);
	}

	public function options(string $path, $handler): self
	{
		return $this->map(['OPTIONS'], $path, $handler);
	}
}
