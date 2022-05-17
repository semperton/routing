<?php

declare(strict_types=1);

namespace Semperton\Routing;

interface RouteCollectionInterface
{
	public function getRouteTree(): RouteNode;
}
