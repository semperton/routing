<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class Node
{
	/** @var bool */
	public $leaf = false;

	/** @var array */
	public $handler = [];

	/** @var array */
	public $static = [];

	/** @var array */
	public $placeholder = [];

	/** @var array */
	public $catchall = [];

	public static function __set_state($props): Node
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
