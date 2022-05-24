<?php

declare(strict_types=1);

namespace Semperton\Routing\Matcher;

use Semperton\Routing\MatchResult;

interface PathMatcherInterface
{
	public function match(string $method, string $path): MatchResult;
}
