<?php

declare(strict_types=1);

namespace Semperton\Routing\Matcher;

use Psr\Http\Message\ServerRequestInterface;
use Semperton\Routing\MatchResult;

interface RequestMatcherInterface
{
	public function matchRequest(ServerRequestInterface $request): MatchResult;
}
