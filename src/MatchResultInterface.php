<?php

declare(strict_types=1);

namespace Semperton\Routing;

interface MatchResultInterface
{
	public function isMatch(): bool;
	/**
	 * @return mixed
	 */
	public function getHandler();
	public function getMethods(): array;
	public function getParams(): array;
}
