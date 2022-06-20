<?php

declare(strict_types=1);

namespace Semperton\Routing\Matcher;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Semperton\Routing\Collection\RouteCollectionInterface;
use Semperton\Routing\MatchResult;
use Semperton\Routing\RouteNode;
use Semperton\Routing\RoutingTrait;

use function strpos;
use function substr;
use function strlen;
use function explode;
use function array_slice;
use function array_merge;
use function array_unshift;
use function implode;
use function array_unique;
use function array_keys;
use function str_replace;
use function ctype_alnum;
use function rawurldecode;

class RouteMatcher implements PathMatcherInterface, RequestMatcherInterface
{
	use RoutingTrait;

	/** @var array<string, callable> */
	protected array $validators = [
		'A' => 'ctype_alnum',
		'a' => 'ctype_alpha',
		'd' => 'ctype_digit',
		'x' => 'ctype_xdigit',
		'l' => 'ctype_lower',
		'u' => 'ctype_upper'
	];

	protected string $basePath = '';

	protected RouteNode $routeTree;

	public function __construct(RouteCollectionInterface $routeCollection)
	{
		$this->routeTree = $routeCollection->getRouteData()->getRouteTree();
		$this->validators['w'] = [$this, 'validateWord'];
	}

	/**
	 * @param string $path MUST be percent-encoded
	 */
	public function setBasePath(string $path): self
	{
		$this->basePath = $path;
		return $this;
	}

	public function getBasePath(): string
	{
		return $this->basePath;
	}

	public function setValidator(string $id, callable $callback): self
	{
		$this->validators[$id] = $callback;
		return $this;
	}

	public function match(string $method, string $path): MatchResult
	{
		if ($method === 'HEAD') { // HEAD is the same as GET
			$method = 'GET';
		}

		if ($this->basePath !== '' && strpos($path, $this->basePath) === 0) {
			$path = substr($path, strlen($this->basePath));
		}

		$tokens = $this->generateTokens($path);
		$params = [];

		return $this->resolve($this->routeTree, $tokens, $method, $params);
	}

	public function matchRequest(ServerRequestInterface $request): MatchResult
	{
		$method = $request->getMethod();
		$path = $request->getUri()->getPath();

		return $this->match($method, $path);
	}

	/**
	 * @param array<int, string> $tokens
	 * @param array<string, string> $params
	 */
	protected function resolve(RouteNode $node, array $tokens, string $method, array &$params): MatchResult
	{
		foreach ($tokens as $i => $token) {

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
					}

					$methods = $result->getMethods();

					if (!!$methods) {
						$allowedMethods = array_merge($allowedMethods, $methods);
					}

					unset($params[$split[0]]);
				}
			}

			foreach ($node->catchall as $cname => $_) { // catchall

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

			$match = isset($node->handler[$method]) || array_key_exists($method, $node->handler);

			/** @var mixed */
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
