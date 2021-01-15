<?php

declare(strict_types=1);

namespace Semperton\Routing;

use Closure;

class RouteCollection
{
	protected $tree;

	protected $prefix = '';

	public function __construct(?array $tree = null)
	{
		$this->tree = $tree ?? $this->newNode();
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

		$this->build($this->tree, $tokens, $handler);

		return $this;
	}

	protected function build(array &$node, array $tokens, array $handler): void
	{
		$token = array_shift($tokens);

		while (!empty($token)) { // "" or null

			if ($token[0] === '*') { // catchall
				$token = substr($token, 1);
				$node['catchall'][$token] = true;
				break;
			}

			$treePath = &$node['static'];

			if ($token[0] === ':') { // placeholder
				$treePath = &$node['placeholder'];
				$token = substr($token, 1);
			}

			if (!isset($treePath[$token])) {
				$treePath[$token] = $this->newNode();
			}

			$node = &$treePath[$token];

			$token = array_shift($tokens);
		}

		$node['leaf'] = true;
		$node['handler'] = $handler + $node['handler'];
	}

	protected function newNode(): array
	{
		return [
			'leaf' => false,
			'handler' => [],
			'static' => [],
			'placeholder' => [],
			'catchall' => []
		];
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
