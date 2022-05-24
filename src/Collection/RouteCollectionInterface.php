<?php

declare(strict_types=1);

namespace Semperton\Routing\Collection;

use Semperton\Routing\RouteData;

interface RouteCollectionInterface
{
	public function getRouteData(): RouteData;
}
