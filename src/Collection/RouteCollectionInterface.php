<?php

declare(strict_types=1);

namespace Semperton\Routing\Collection;

use Semperton\Routing\RouteNode;

interface RouteCollectionInterface
{
	/**
	 * The returned tree MUST be treated as read-only.
	 * RouteNode is internal, build the tree with RouteCollection instead of implementing this interface.
	 */
	public function getRouteTree(): RouteNode;
}
