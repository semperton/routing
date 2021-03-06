<?php

declare(strict_types=1);

namespace Semperton\Routing;

use Closure;
use InvalidArgumentException;
use OutOfBoundsException;

class RouteCollection
{
	/** @var string */
	protected $pathPrefix = '';

	/** @var string */
	protected $namePrefix = '';

	/** @var array */
	protected $namedRoutes = [];

	/** @var Node */
	protected $routeTree;

	public function __construct()
	{
		$this->routeTree = new Node();
	}

	public function routeTree(): Node
	{
		return clone $this->routeTree;
	}

	public function reverse(string $name, array $params): string
	{
		if (!isset($this->namedRoutes[$name])) {
			throw new OutOfBoundsException("The route with name < $name > does not exist");
		}

		$path = $this->namedRoutes[$name];

		$leadingSlash = $path[0] === '/';
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

		$path = ($leadingSlash ? '/' : '') . implode('/', $tokens) . ($trailingSlash ? '/' : '');

		return $path;
	}

	public function group(string $path, Closure $callback, string $name = ''): self
	{
		$currentPath = $this->pathPrefix;
		$currentName = $this->namePrefix;

		$this->pathPrefix .= $path;
		$this->namePrefix .= $name;

		$callback($this);

		$this->pathPrefix = $currentPath;
		$this->namePrefix = $currentName;

		return $this;
	}

	public function map(array $methods, string $path, $target, string $name = ''): self
	{
		$handler = [];
		foreach ($methods as $method) {
			$method = strtoupper($method);
			$handler[$method] = $target;
		}

		$path = $this->pathPrefix . $path;

		if ($name !== '') {
			$name = $this->namePrefix . $name;
			$this->namedRoutes[$name] = $path;
		}

		$tokens = explode('/', trim($path, '/'));
		$this->mapTokens($this->routeTree, $tokens, $handler);

		return $this;
	}

	protected function mapTokens(Node $node, array $tokens, array $handler): void
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
				$token = substr($token, 1);
				$key = 'placeholder';
			}

			$path = &$node->{$key};

			if (!isset($path[$token])) {
				$path[$token] = new Node();
			}

			$node = $path[$token];
		}

		$node->leaf = true;
		$node->handler = $handler + $node->handler;
	}

	public function get(string $path, $handler, string $name = ''): self
	{
		return $this->map(['GET'], $path, $handler, $name);
	}

	public function post(string $path, $handler, string $name = ''): self
	{
		return $this->map(['POST'], $path, $handler, $name);
	}

	public function put(string $path, $handler, string $name = ''): self
	{
		return $this->map(['PUT'], $path, $handler, $name);
	}

	public function delete(string $path, $handler, string $name = ''): self
	{
		return $this->map(['DELETE'], $path, $handler, $name);
	}

	public function patch(string $path, $handler, string $name = ''): self
	{
		return $this->map(['PATCH'], $path, $handler, $name);
	}

	public function head(string $path, $handler, string $name = ''): self
	{
		return $this->map(['HEAD'], $path, $handler, $name);
	}

	public function options(string $path, $handler, string $name = ''): self
	{
		return $this->map(['OPTIONS'], $path, $handler, $name);
	}
}
