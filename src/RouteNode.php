<?php

declare(strict_types=1);

namespace Semperton\Routing;

use Closure;

/**
 * The tree structure is an implementation detail and may change in any release
 *
 * @internal
 */
final class RouteNode
{
	/** @var array<string, mixed> method => handler, empty for non-leaf nodes */
	public array $handler = [];

	/** @var array<string, array<string, mixed>> full static path => handler, only filled on the root node */
	public array $paths = [];

	/** @var array<string, RouteNode> */
	public array $static = [];

	/** @var array<string, RouteNode> */
	public array $placeholder = [];

	/** @var array<string, RouteNode> */
	public array $catchall = [];

	/** parameter name of placeholder / catchall nodes */
	public string $param = '';

	/** @var null|Closure(string): bool validator of placeholder / catchall nodes */
	public ?Closure $validator = null;

	public function __clone()
	{
		foreach ($this->static as $path => $node) {
			$this->static[$path] = clone $node;
		}

		foreach ($this->placeholder as $path => $node) {
			$this->placeholder[$path] = clone $node;
		}

		foreach ($this->catchall as $path => $node) {
			$this->catchall[$path] = clone $node;
		}
	}
}
