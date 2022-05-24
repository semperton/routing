<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class RouteData
{
	protected RouteNode $routeTree;

	/** @var array<string, array<int, string>> */
	protected array $namedRoutes;

	/**
	 * @param array<string, array<int, string>> $namedRoutes
	 */
	public function __construct(RouteNode $routeTree, array $namedRoutes)
	{
		$this->routeTree = $routeTree;
		$this->namedRoutes = $namedRoutes;
	}

	public function __clone()
	{
		$this->routeTree = clone $this->routeTree;
	}

	public function getRouteTree(): RouteNode
	{
		return $this->routeTree;
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function getNamedRoutes(): array
	{
		return $this->namedRoutes;
	}
}
