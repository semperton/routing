<?php

declare(strict_types=1);

namespace Semperton\Routing;

use InvalidArgumentException;
use Semperton\Routing\RouteCollection as RC;

class RouteMatcher implements RouteMatcherInterface
{
	protected $routeTree;

	protected $basePath = '';

	// https://www.php.net/manual/en/ref.ctype.php
	protected $validators = [
		'A' => 'ctype_alnum',
		'a' => 'ctype_alpha',
		'd' => 'ctype_digit',
		'x' => 'ctype_xdigit',
		'l' => 'ctype_lower',
		'u' => 'ctype_upper'
	];

	public function __construct(array $routeTree)
	{
		$this->routeTree = $routeTree;
		$this->setValidator('w', [$this, 'validateWord']);
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

	public function match(string $method, string $path, array $params = []): MatchResult
	{
		if ($this->basePath !== '' && strpos($path, $this->basePath) === 0) {
			$path = substr($path, strlen($this->basePath));
		}

		$path = trim($path, '/');
		$tokens = explode('/', $path);

		return $this->resolve($this->routeTree, $tokens, $method, $params);
	}

	protected function resolve(array $node, array $tokens, string $method, array $params): MatchResult
	{
		foreach ($tokens as $index => $token) {

			if ($token === '') { // index
				break;
			}

			if (isset($node[RC::NODE_STATIC][$token])) { // static path
				$node = $node[RC::NODE_STATIC][$token];
				continue;
			}

			if (isset($node[RC::NODE_PLACEHOLDER])) { // placeholder

				$allowedMethods = [];

				$placeholder = $node[RC::NODE_PLACEHOLDER];
				$tokensLeft = array_slice($tokens, $index + 1);

				foreach ($placeholder as $pname => $pnode) {

					$split = explode(':', $pname);

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
			}

			if (isset($node[RC::NODE_CATCHALL])) {

				$catchall = $node[RC::NODE_CATCHALL];

				foreach ($catchall as $cname => $val) { // check catchall

					$split = explode(':', $cname);

					if (empty($split[1]) || $this->validate($token, $split[1])) {

						$params[$split[0]] = implode('/', array_slice($tokens, $index));
						break 2;
					}
				}
			}

			return new MatchResult(false, null, array_unique($allowedMethods)); // token mismatch
		}

		if (!empty($node[RC::NODE_LEAF])) {

			if ($method === 'HEAD' && !isset($node[RC::NODE_HANDLER][$method])) { // HEAD fallback
				$method = 'GET';
			}

			$match = isset($node[RC::NODE_HANDLER][$method]);
			$handler = $match ? $node[RC::NODE_HANDLER][$method] : null;
			$methods = isset($node[RC::NODE_HANDLER]) ? array_keys($node[RC::NODE_HANDLER]) : [];

			return new MatchResult($match, $handler, $methods, $params);
		}

		return new MatchResult(false); // not found
	}

	public function validate(string $value, string $type): bool
	{
		if (!isset($this->validators[$type])) {
			throw new InvalidArgumentException("No validation function found for < :$type >");
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
