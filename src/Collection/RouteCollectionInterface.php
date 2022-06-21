<?php

declare(strict_types=1);

namespace Semperton\Routing\Collection;

use Semperton\Routing\RouteNode;

interface RouteCollectionInterface
{
	public function getRouteTree(): RouteNode;
}
