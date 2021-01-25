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

	/** @var Route[] */
	protected $routes = [];

	/** @var Builder */
	protected $builder;

	public function __construct()
	{
		$this->builder = new Builder();
	}

	public function toArray(): array
	{
		$routes = [];
		foreach ($this->routes as $route) {
			$routes[] = [$route->method, $route->path, $route->handler];
		}
		return $routes;
	}

	public function toTree(): TreeNode
	{
		return $this->builder->buildTree($this->routes);
	}

	public function reverse(string $name, array $params): string
	{
		if (!isset($this->routes[$name])) {
			throw new OutOfBoundsException("The route with name < $name > does not exist");
		}

		$route = $this->routes[$name];

		return $this->builder->buildRoute($route, $params);
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

	public function map(array $methods, string $path, $handler, string $name = ''): self
	{
		foreach ($methods as $method) {

			$method = strtoupper($method);
			$path = $this->pathPrefix . $path;
			$route = new Route($method, $path, $handler);

			if ($name === '') {
				$this->routes[] = $route;
			} else {
				$name = $this->namePrefix . $name;
				$this->routes[$name] = $route;
			}
		}

		return $this;
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
