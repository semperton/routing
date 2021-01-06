<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class TreeNode
{
	public $isLeaf = false;
	public $handler = [];
	public $children = [];
	public $placeholder = [];
	public $catchall = [];
}
