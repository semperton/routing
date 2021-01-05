<?php

declare(strict_types=1);

namespace Semperton\Routing;

interface RouteMatcherInterface
{
	public function match(string $method, string $path): MatchResult;
	public function build(string $routeName, array $params): string;
}
