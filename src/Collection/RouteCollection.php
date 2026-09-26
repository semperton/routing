<?php

declare(strict_types=1);

namespace Semperton\Routing\Collection;

use Closure;
use InvalidArgumentException;
use OutOfBoundsException;
use Semperton\Routing\RouteNode;

use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_map;
use function count;
use function explode;
use function implode;
use function rawurlencode;
use function str_contains;
use function strlen;
use function strspn;
use function substr;

class RouteCollection implements RouteCollectionInterface
{
	private const DIGIT = '0123456789';
	private const LOWER = 'abcdefghijklmnopqrstuvwxyz';
	private const UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

	protected string $pathPrefix = '';

	protected string $namePrefix = '';

	/** @var array<string, list<string>> */
	protected array $namedRoutes = [];

	/** @var array<string, Closure(string): bool> */
	protected array $validators;

	protected RouteNode $routeTree;

	public function __construct()
	{
		$this->routeTree = new RouteNode();

		// locale independent, only the given ASCII chars are allowed
		$ascii = static fn (string $chars): Closure =>
			static fn (string $value): bool => $value !== '' && strspn($value, $chars) === strlen($value);

		$this->validators = [
			'A' => $ascii(self::LOWER . self::UPPER . self::DIGIT),
			'a' => $ascii(self::LOWER . self::UPPER),
			'd' => $ascii(self::DIGIT),
			'x' => $ascii(self::DIGIT . 'abcdefABCDEF'),
			'l' => $ascii(self::LOWER),
			'u' => $ascii(self::UPPER),
			'w' => $ascii(self::LOWER . self::UPPER . self::DIGIT . '_')
		];
	}

	public function __clone()
	{
		$this->routeTree = clone $this->routeTree;
	}

	public function getRouteTree(): RouteNode
	{
		return $this->routeTree;
	}

	/**
	 * Validators MUST be defined before the routes using them and cannot be replaced
	 *
	 * @param callable(string): bool $callback receives the decoded segment
	 */
	public function setValidator(string $id, callable $callback): static
	{
		if ($id === '') {
			throw new InvalidArgumentException('The validator id must not be empty');
		}

		if (isset($this->validators[$id])) {
			throw new InvalidArgumentException("The validator < $id > already exists");
		}

		$this->validators[$id] = static fn (string $value): bool => $callback($value);

		return $this;
	}

	public function validate(string $value, string $id): bool
	{
		return $this->getValidator($id)($value);
	}

	/**
	 * @return Closure(string): bool
	 */
	protected function getValidator(string $id): Closure
	{
		return $this->validators[$id] ?? throw new InvalidArgumentException(
			"Validator < $id > not found in (" . implode(', ', array_keys($this->validators)) . ')'
		);
	}

	/**
	 * @param array<string, scalar> $params decoded values, they get percent-encoded
	 */
	public function reverse(string $name, array $params = []): string
	{
		$tokens = $this->namedRoutes[$name] ?? throw new OutOfBoundsException("The route with name < $name > does not exist");

		$path = [];

		foreach ($tokens as $token) {

			$first = $token[0] ?? '';

			if ($first !== ':' && $first !== '*') { // static
				$path[] = rawurlencode($token);
				continue;
			}

			[$param, $validator] = explode(':', substr($token, 1), 2) + [1 => ''];

			if (!isset($params[$param])) {
				throw new InvalidArgumentException("No value defined for placeholder < $param >");
			}

			$value = (string)$params[$param];

			if (($first === ':' && $value === '') || ($validator !== '' && !$this->validate($value, $validator))) {
				throw new InvalidArgumentException("Invalid value < $value > for placeholder < $param >");
			}

			$path[] = $first === '*'
				? implode('/', array_map('rawurlencode', explode('/', $value)))
				: rawurlencode($value);
		}

		return implode('/', $path);
	}

	public function group(string $path, Closure $callback, string $name = ''): static
	{
		$currentPath = $this->pathPrefix;
		$currentName = $this->namePrefix;

		$this->pathPrefix .= $path;
		$this->namePrefix .= $name;

		try {
			$callback($this);
		} finally {
			$this->pathPrefix = $currentPath;
			$this->namePrefix = $currentName;
		}

		return $this;
	}

	/**
	 * @param list<string> $methods case-sensitive, e.g. 'GET'
	 */
	public function map(array $methods, string $path, mixed $handler, string $name = ''): static
	{
		$path = $this->pathPrefix . $path;

		if ($methods === []) {
			throw new InvalidArgumentException("No methods defined for route < $path >");
		}

		if ($path !== '' && $path[0] !== '/') {
			throw new InvalidArgumentException("The route < $path > must start with a slash");
		}

		if (str_contains($path, '//')) {
			throw new InvalidArgumentException("The route < $path > must not contain empty segments");
		}

		if ($name !== '') {
			$name = $this->namePrefix . $name;

			if (isset($this->namedRoutes[$name])) {
				throw new InvalidArgumentException("The route with name < $name > already exists");
			}
		}

		$tokens = $path === '' ? [] : explode('/', $path);

		$this->mapTokens($tokens, array_fill_keys($methods, $handler));

		if ($name !== '') {
			$this->namedRoutes[$name] = $tokens;
		}

		return $this;
	}

	/**
	 * @param list<string> $tokens
	 * @param array<string, mixed> $handler
	 */
	protected function mapTokens(array $tokens, array $handler): void
	{
		$node = $this->routeTree;
		$static = true;
		$params = [];
		$last = count($tokens) - 1;

		foreach ($tokens as $i => $token) {

			$first = $token[0] ?? '';

			if ($first !== ':' && $first !== '*') { // static
				$node = $node->static[$token] ??= new RouteNode();
				continue;
			}

			$static = false;
			$key = substr($token, 1);
			[$param, $validator] = explode(':', $key, 2) + [1 => ''];

			if ($param === '') {
				throw new InvalidArgumentException("Missing parameter name in < $token >");
			}

			if (isset($params[$param])) {
				throw new InvalidArgumentException("Duplicate parameter name < $param >");
			}
			$params[$param] = true;

			$callback = $validator === '' ? null : $this->getValidator($validator);

			if ($first === '*') { // catchall
				if ($i !== $last) {
					throw new InvalidArgumentException("Catchall < $token > must be the last path segment");
				}
				$node = $node->catchall[$key] ??= new RouteNode();
			} else { // placeholder
				$node = $node->placeholder[$key] ??= new RouteNode();
			}

			$node->param = $param;
			$node->validator = $callback;
		}

		foreach ($handler as $method => $_) {
			if (array_key_exists($method, $node->handler)) {
				throw new InvalidArgumentException("The route < $method " . implode('/', $tokens) . ' > already exists');
			}
		}

		$node->handler = $handler + $node->handler;

		if ($static) { // fast lookup for fully static routes
			$this->routeTree->paths[implode('/', $tokens)] = $node->handler;
		}
	}

	public function get(string $path, mixed $handler, string $name = ''): static
	{
		return $this->map(['GET'], $path, $handler, $name);
	}

	public function post(string $path, mixed $handler, string $name = ''): static
	{
		return $this->map(['POST'], $path, $handler, $name);
	}

	public function put(string $path, mixed $handler, string $name = ''): static
	{
		return $this->map(['PUT'], $path, $handler, $name);
	}

	public function delete(string $path, mixed $handler, string $name = ''): static
	{
		return $this->map(['DELETE'], $path, $handler, $name);
	}

	public function patch(string $path, mixed $handler, string $name = ''): static
	{
		return $this->map(['PATCH'], $path, $handler, $name);
	}

	public function options(string $path, mixed $handler, string $name = ''): static
	{
		return $this->map(['OPTIONS'], $path, $handler, $name);
	}
}
