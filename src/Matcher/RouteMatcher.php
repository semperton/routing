<?php

declare(strict_types=1);

namespace Semperton\Routing\Matcher;

use Psr\Http\Message\ServerRequestInterface;
use Semperton\Routing\Collection\RouteCollectionInterface;
use Semperton\Routing\MatchResult;
use Semperton\Routing\RouteNode;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function str_contains;

class RouteMatcher implements PathMatcherInterface, RequestMatcherInterface
{
	public function __construct(
		protected RouteCollectionInterface $routeCollection
	) {
	}

	public function match(string $method, string $path): MatchResult
	{
		$tree = $this->routeCollection->getRouteTree();
		$encoded = str_contains($path, '%');

		if (!$encoded && isset($tree->paths[$path])) {

			$result = $this->leafResult($tree->paths[$path], $method, []);

			if ($result->isMatch()) {
				return $result;
			}
		}

		$tokens = $path === '' ? [] : explode('/', $path);

		if ($encoded) { // split first, so %2F stays part of its segment
			$tokens = array_map('rawurldecode', $tokens);
		}

		$params = [];

		return $this->resolve($tree, $tokens, 0, count($tokens), $method, $params);
	}

	public function matchRequest(ServerRequestInterface $request): MatchResult
	{
		$path = $request->getUri()->getPath();

		// an empty path is equivalent to '/' (RFC 3986)
		return $this->match($request->getMethod(), $path === '' ? '/' : $path);
	}

	/**
	 * Precedence: static > placeholder > catchall
	 *
	 * @param list<string> $tokens
	 * @param array<string, string> $params
	 */
	protected function resolve(RouteNode $node, array $tokens, int $i, int $count, string $method, array &$params): MatchResult
	{
		for (; $i < $count; $i++) {

			$token = $tokens[$i];
			$static = $node->static[$token] ?? null;

			if ($node->placeholder === [] && $node->catchall === []) { // nothing to backtrack to
				if ($static === null) {
					return new MatchResult(false);
				}
				$node = $static;
				continue;
			}

			$allowedMethods = [];

			if ($static !== null) {

				$result = $this->resolve($static, $tokens, $i + 1, $count, $method, $params);

				if ($result->isMatch()) {
					return $result;
				}

				$allowedMethods = $result->getMethods();
			}

			foreach ($node->placeholder as $pnode) {

				if ($token !== '' && ($pnode->validator === null || ($pnode->validator)($token))) {

					$params[$pnode->param] = $token;
					$result = $this->resolve($pnode, $tokens, $i + 1, $count, $method, $params);

					if ($result->isMatch()) {
						return $result;
					}

					$allowedMethods = [...$allowedMethods, ...$result->getMethods()];
					unset($params[$pnode->param]);
				}
			}

			if ($node->catchall !== []) {

				$rest = implode('/', array_slice($tokens, $i));

				foreach ($node->catchall as $cnode) {

					if ($cnode->validator === null || ($cnode->validator)($rest)) {

						$params[$cnode->param] = $rest;
						$result = $this->leafResult($cnode->handler, $method, $params);

						if ($result->isMatch()) {
							return $result;
						}

						$allowedMethods = [...$allowedMethods, ...$result->getMethods()];
						unset($params[$cnode->param]);
					}
				}
			}

			return new MatchResult(false, null, array_values(array_unique($allowedMethods)));
		}

		return $this->leafResult($node->handler, $method, $params);
	}

	/**
	 * @param array<string, mixed> $handler
	 * @param array<string, string> $params
	 */
	protected function leafResult(array $handler, string $method, array $params): MatchResult
	{
		if ($handler === []) {
			return new MatchResult(false);
		}

		if (!array_key_exists($method, $handler)) {

			// HEAD falls back to GET, unless a HEAD route is defined
			if ($method !== 'HEAD' || !array_key_exists('GET', $handler)) {
				return new MatchResult(false, null, array_keys($handler));
			}

			$method = 'GET';
		}

		return new MatchResult(true, $handler[$method], array_keys($handler), $params);
	}
}
