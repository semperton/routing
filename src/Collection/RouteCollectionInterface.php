<?php

declare(strict_types=1);

namespace Semperton\Routing\Collection;

use Semperton\Routing\RouteNode;

interface RouteCollectionInterface
{
	/**
	 * The returned tree MUST be treated as read-only
	 */
	public function getRouteTree(): RouteNode;
}
