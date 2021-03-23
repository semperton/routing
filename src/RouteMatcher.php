<?php

declare(strict_types=1);

namespace Semperton\Routing;

use InvalidArgumentException;

class RouteMatcher implements RouteMatcherInterface
{
	// https://www.php.net/manual/en/ref.ctype.php
	protected $validators = [
		'A' => 'ctype_alnum',
		'a' => 'ctype_alpha',
		'd' => 'ctype_digit',
		'x' => 'ctype_xdigit',
		'l' => 'ctype_lower',
		'u' => 'ctype_upper'
	];

	protected $basePath = '';

	/** @var Node */
	protected $routeTree;

	public function __construct(Node $routeTree)
	{
		$this->routeTree = $routeTree;
		$this->validators['w'] = [$this, 'validateWord'];
	}

	public function setBasePath(string $path): self
	{
		$this->basePath = $path;
		return $this;
	}

	public function setValidator(string $id, callable $callback): self
	{
		$this->validators[$id] = $callback;
		return $this;
	}

	public function match(string $method, string $path): MatchResultInterface
	{
		if ($this->basePath !== '' && strpos($path, $this->basePath) === 0) {
			$path = substr($path, strlen($this->basePath));
		}

		$tokens = explode('/', trim($path, '/'));

		return $this->resolve($this->routeTree, $tokens, $method, []);
	}

	protected function resolve(Node $node, array $tokens, string $method, array $params): MatchResult
	{
		foreach ($tokens as $i => $token) {

			if ($token === '') { // index
				break;
			}

			if (isset($node->static[$token])) { // static path
				$node = $node->static[$token];
				continue;
			}

			$allowedMethods = [];
			$tokensLeft = array_slice($tokens, $i + 1);

			foreach ($node->placeholder as $pname => $pnode) { // placeholder

				$split = explode(':', $pname, 2);

				if (empty($split[1]) || $this->validate($token, $split[1])) {

					$params[$split[0]] = $token;
					$result = $this->resolve($pnode, $tokensLeft, $method, $params);

					if ($result->isMatch()) {
						return $result;
					} else if (!empty($result->getMethods())) {
						$allowedMethods = array_merge($allowedMethods, $result->getMethods());
					}

					unset($params[$split[0]]);
				}
			}

			foreach ($node->catchall as $cname => $val) { // catchall

				$split = explode(':', $cname, 2);

				if (empty($split[1]) || $this->validate($token, $split[1])) {

					array_unshift($tokensLeft, $token);
					$params[$split[0]] = implode('/', $tokensLeft);
					break 2;
				}
			}

			return new MatchResult(false, null, array_unique($allowedMethods)); // token mismatch
		}

		if ($node->leaf) {

			if ($method === 'HEAD' && !isset($node->handler[$method])) { // HEAD fallback
				$method = 'GET';
			}

			$match = isset($node->handler[$method]);
			$handler = $match ? $node->handler[$method] : null;
			$methods = array_keys($node->handler);

			return new MatchResult($match, $handler, $methods, $params);
		}

		return new MatchResult(false); // not found
	}

	public function validate(string $value, string $type): bool
	{
		if (!isset($this->validators[$type])) {
			$validators = implode(', ', array_keys($this->validators));
			throw new InvalidArgumentException("Validator < $type > not found in ($validators)");
		}

		$callback = $this->validators[$type];

		return (bool)$callback($value);
	}

	protected static function validateWord(string $value): bool
	{
		if ($value === '') {
			return false;
		}

		$value = str_replace('_', '', $value);

		if ($value === '') {
			return true;
		}

		return ctype_alnum($value);
	}
}
