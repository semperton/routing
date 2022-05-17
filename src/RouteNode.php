<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class RouteNode
{
	/** @var bool */
	public $leaf = false;

	/** @var array<string, mixed> */
	public $handler = [];

	/** @var array<string, RouteNode> */
	public $static = [];

	/** @var array<string, RouteNode> */
	public $placeholder = [];

	/** @var array<string, true> */
	public $catchall = [];

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
