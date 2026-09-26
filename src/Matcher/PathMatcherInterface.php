<?php

declare(strict_types=1);

namespace Semperton\Routing\Matcher;

use Semperton\Routing\MatchResult;

interface PathMatcherInterface
{
	/**
	 * @param string $path MUST be percent-encoded, params of the result are decoded
	 */
	public function match(string $method, string $path): MatchResult;
}
