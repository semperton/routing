<?php

declare(strict_types=1);

namespace Semperton\Routing\Collection;

use Closure;
use InvalidArgumentException;
use OutOfBoundsException;
use Semperton\Routing\RouteNode;
use Semperton\Routing\RoutingTrait;

use function explode;
use function substr;
use function array_slice;
use function implode;
use function strtoupper;

class RouteCollection implements RouteCollectionInterface
{
	use RoutingTrait;

	protected string $pathPrefix = '';

	protected string $namePrefix = '';

	/** @var array<string, array<int, string>> */
	protected array $namedRoutes;

	protected RouteNode $routeTree;

	/**
	 * @param array<string, array<int, string>> $namedRoutes
	 */
	public function __construct(
		?RouteNode $routeTree = null,
		array $namedRoutes = []
	) {
		$this->routeTree = $routeTree ?? new RouteNode();
		$this->namedRoutes = $namedRoutes;
	}

	public function __clone()
	{
		$this->routeTree = clone $this->routeTree;
	}

	public function getRouteTree(): RouteNode
	{
		return clone $this->routeTree;
	}

	/**
	 * @param array<string, scalar> $params
	 */
	public function reverse(string $name, array $params): string
	{
		if (!isset($this->namedRoutes[$name])) {
			throw new OutOfBoundsException("The route with name < $name > does not exist");
		}

		$tokens = $this->namedRoutes[$name];

		foreach ($tokens as $i => &$token) {

			if ($token === '') {
				continue;
			}

			$first = $token[0];

			if ($first === ':' || $first === '*') {

				$param = explode(':', substr($token, 1), 2)[0];

				if (!isset($params[$param])) {
					throw new InvalidArgumentException("No value defined for placeholder < $param >");
				}

				$token = rawurlencode((string)$params[$param]);

				if ($first === '*') {
					$tokens = array_slice($tokens, 0, $i + 1);
					break;
				}
			}
		}

		return implode('/', $tokens);
	}

	public function dump(): string
	{
		return $this->export([
			't' => $this->routeTree,
			'n' => $this->namedRoutes
		]);
	}

	/**
	 * @param array{t: RouteNode, n: array<string, array<int, string>>} $data
	 */
	public static function fromArray(array $data): self
	{
		return new self($data['t'], $data['n']);
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

	/**
	 * @param array<int, string> $methods
	 * @param mixed $handler
	 */
	public function map(array $methods, string $path, $handler, string $name = ''): self
	{
		$mapping = [];
		foreach ($methods as $method) {
			$method = strtoupper($method);
			/** @psalm-suppress MixedAssignment */
			$mapping[$method] = $handler;
		}

		$path = $this->pathPrefix . $path;

		$tokens = $this->generateTokens($path);

		if ($name !== '') {
			$name = $this->namePrefix . $name;
			$this->namedRoutes[$name] = $tokens;
		}

		$this->mapTokens($this->routeTree, $tokens, $mapping);

		return $this;
	}

	/**
	 * @param array<int, string> $tokens
	 * @param array<string, mixed> $handler
	 */
	protected function mapTokens(RouteNode $node, array $tokens, array $handler): void
	{
		foreach ($tokens as $token) {

			$key = 'static';

			if ($token !== '') {

				$first = $token[0];

				if ($first === '*') { // catchall
					$token = substr($token, 1);
					$node->catchall[$token] = true;
					break;
				}

				if ($first === ':') { // placeholder
					$token = substr($token, 1);
					$key = 'placeholder';
				}
			}

			/** @var array */
			$path = &$node->{$key};

			if (!isset($path[$token])) {
				$path[$token] = new RouteNode();
			}

			/** @var RouteNode */
			$node = $path[$token];
		}

		$node->leaf = true;
		$node->handler = $handler + $node->handler;
	}

	/**
	 * @param mixed $handler
	 */
	public function get(string $path, $handler, string $name = ''): self
	{
		return $this->map(['GET'], $path, $handler, $name);
	}

	/**
	 * @param mixed $handler
	 */
	public function post(string $path, $handler, string $name = ''): self
	{
		return $this->map(['POST'], $path, $handler, $name);
	}

	/**
	 * @param mixed $handler
	 */
	public function put(string $path, $handler, string $name = ''): self
	{
		return $this->map(['PUT'], $path, $handler, $name);
	}

	/**
	 * @param mixed $handler
	 */
	public function delete(string $path, $handler, string $name = ''): self
	{
		return $this->map(['DELETE'], $path, $handler, $name);
	}

	/**
	 * @param mixed $handler
	 */
	public function patch(string $path, $handler, string $name = ''): self
	{
		return $this->map(['PATCH'], $path, $handler, $name);
	}

	/**
	 * @param mixed $handler
	 */
	public function options(string $path, $handler, string $name = ''): self
	{
		return $this->map(['OPTIONS'], $path, $handler, $name);
	}
}
