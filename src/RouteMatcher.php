<?php

declare(strict_types=1);

namespace Semperton\Routing;

class RouteMatcher implements RouteMatcherInterface
{
	protected $routeCollection;

	protected $basePath = '';

	// https://www.php.net/manual/en/ref.ctype.php
	protected $validationFunctions = [
		'A' => 'ctype_alnum',
		'a' => 'ctype_alpha',
		'd' => 'ctype_digit',
		'x' => 'ctype_xdigit',
		'l' => 'ctype_lower',
		'u' => 'ctype_upper'
	];

	public function __construct(RouteCollection $routeCollection)
	{
		$this->routeCollection = $routeCollection;
		$this->addValidationFunction('w', [$this, 'validateWord']);
	}

	public function setBasePath(string $path): self
	{
		$this->basePath = $path;

		return $this;
	}

	public function addValidationFunction(string $id, callable $callback): self
	{
		if (is_callable($callback)) {
			$this->validationFunctions[$id] = $callback;
		}

		return $this;
	}

	public function match(string $method, string $path, array $params = []): MatchResult
	{
		if (!empty($this->basePath) && strpos($path, $this->basePath) === 0) {
			$path = substr($path, strlen($this->basePath));
		}

		$path = trim($path, '/');

		$tokens = explode('/', $path);

		$tree = $this->routeCollection->getTree();

		return $this->resolve($tree, $tokens, $method, $params);
	}

	protected function resolve(array $node, array $tokens, string $method, array $params): MatchResult
	{
		$token = array_shift($tokens);

		if (empty($token)) { // end of path, "" or null

			if ($node['leaf']) {

				if ($method === 'HEAD' && !isset($node['handler'][$method])) { // HEAD fallback
					$method = 'GET';
				}

				$match = isset($node['handler'][$method]);
				$handler = $match ? $node['handler'][$method] : null;
				$methods = array_keys($node['handler']);

				return new MatchResult($match, $handler, $methods, $params);
			}
		} else if (isset($node['static'][$token])) { // regular token

			return $this->resolve($node['static'][$token], $tokens, $method, $params);
		} else {

			foreach ($node['placeholder'] as $pname => $pnode) { // check placeholder

				$split = explode(':', $pname);

				if (empty($split[1]) || $this->validate($token, $split[1])) {

					$result = $this->resolve($pnode, $tokens, $method, $params);

					if ($result->isMatch()) {

						$allParams = $result->getParams();
						$allParams[$split[0]] = $token;

						return new MatchResult(true, $result->getHandler(), $result->getMethods(), $allParams);
					}
				}
			}

			foreach ($node['catchall'] as $cname => $val) { // check catchall

				$split = explode(':', $cname);

				if (empty($split[1]) || $this->validate($token, $split[1])) {

					array_unshift($tokens, $token);
					$params[$split[0]] = implode('/', $tokens);
					return $this->resolve($node, [], $method, $params);
				}
			}
		}

		return new MatchResult(false); // not found
	}

	protected function validate(string $value, string $type): bool
	{
		if (isset($this->validationFunctions[$type])) {

			$callback = $this->validationFunctions[$type];

			return (bool)$callback($value);
		}

		return false;
	}

	protected static function validateWord(string $value): bool
	{
		$value = str_replace(['_', '-'], '', $value);
		return ctype_alnum($value);
	}
}
