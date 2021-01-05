<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class MatchResult
{
	public $isMatch = false;
	public $handler = null;
	public $methods = [];
	public $params = [];
}
