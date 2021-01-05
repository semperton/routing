<?php

declare(strict_types=1);

namespace Semperton\Routing;

use Exception;

class RouteMatcher implements RouteMatcherInterface
{
	protected $routeCollection;

	protected $basePath = '';

	// https://www.php.net/manual/en/ref.ctype.php
	protected $validationTypes = [
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
		$this->registerValidationType('w', [$this, 'validateWord']);
	}

	public function setBasePath(string $path): self
	{
		$this->basePath = $path;

		return $this;
	}

	public function registerValidationType(string $id, callable $callback): self
	{
		if (is_callable($callback)) {
			$this->validationTypes[$id] = $callback;
		}

		return $this;
	}

	public function match(string $method, string $path, array $params = []): MatchResult
	{
		if (!empty($this->basePath) && strpos($path, $this->basePath) === 0) {
			$path = substr($path, strlen($this->basePath));
		}

		$path = str_replace('.', SEPARATOR, $path);
		$path = trim($path, SEPARATOR);

		$tokens = explode(SEPARATOR, $path);

		$tree = $this->routeCollection->getTree();

		return $this->resolve($tree, $tokens, $method, $params);
	}

	public function build(string $routeName, array $params): string
	{
		throw new Exception('Method "build" not yet implemented');
	}

	protected function resolve(TreeNode $node, array $tokens, string $method, array $params): MatchResult
	{
		$result = new MatchResult();

		$token = array_shift($tokens);

		while (!empty($token)) { // "" or null

			if (isset($node->children[$token])) { // regular token

				$node = $node->children[$token];
				$token = array_shift($tokens);
				continue;
			}

			foreach ($node->placeholder as $pname => $pnode) { // check placeholder

				if ($pname[0] === COLON) {

					$split = explode(COLON, trim($pname, COLON));

					if (empty($split[1]) || $this->validate($token, $split[1])) {

						$params[$split[0]] = $token;
						$node = $pnode;
						$token = array_shift($tokens);
						continue 2;
					}
				} else if ($pname[0] === ASTERISK) { // catch all

					$pname = substr($pname, 1);
					$params[$pname] = rtrim($token . SEPARATOR . implode(SEPARATOR, $tokens), SEPARATOR);
					$node = $pnode;
					break 2;
				}
			}

			return $result; // token mismatch
		}

		// check for handler
		if ($node->isLeaf) {

			$result->methods = array_keys($node->handler);
			$result->params = $params;

			if ($method === 'HEAD' && !isset($node->handler[$method])) { // HEAD fallback
				$method = 'GET';
			}

			if (isset($node->handler[$method])) {
				$result->handler = $node->handler[$method];
				$result->isMatch = true;
			}
		}

		return $result;
	}

	protected function validate(string $value, string $type): bool
	{
		if (isset($this->validationTypes[$type])) {

			$callback = $this->validationTypes[$type];

			return (bool)$callback($value);
		}

		return false;
	}

	protected static function validateWord(string $value): bool
	{
		$value = str_replace('_', '', $value);
		return ctype_alpha($value);
	}
}
