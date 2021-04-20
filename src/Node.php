<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class Node
{
	/** @var bool */
	public $leaf = false;

	/** @var array<string, mixed> */
	public $handler = [];

	/** @var array<string, Node> */
	public $static = [];

	/** @var array<string, Node> */
	public $placeholder = [];

	/** @var array<string, true> */
	public $catchall = [];

	public static function __set_state(array $props): Node
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
