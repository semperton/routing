<?php

declare(strict_types=1);

namespace Semperton\Routing;

use function trim;
use function explode;

trait RoutingTrait
{
	/**
	 * @return array<int, string>
	 */
	protected function generateTokens(string $path): array
	{
		$path = trim($path);

		return $path === '' ? [] : explode('/', $path);
	}
}
