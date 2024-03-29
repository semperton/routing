<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class RouteNode
{
	public bool $leaf = false;

	/** @var array<string, mixed> */
	public array $handler = [];

	/** @var array<string, RouteNode> */
	public array $static = [];

	/** @var array<string, RouteNode> */
	public array $placeholder = [];

	/** @var array<string, true> */
	public array $catchall = [];

	public function __clone()
	{
		foreach ($this->static as $path => $node) {
			$this->static[$path] = clone $node;
		}

		foreach ($this->placeholder as $path => $node) {
			$this->placeholder[$path] = clone $node;
		}
	}

	/**
	 * @psalm-suppress MixedAssignment
	 */
	public static function __set_state(array $props): RouteNode
	{
		$node = new self();
		$node->leaf = $props['leaf'];
		$node->handler = $props['handler'];
		$node->static = $props['static'];
		$node->placeholder = $props['placeholder'];
		$node->catchall = $props['catchall'];
		return $node;
	}
}
