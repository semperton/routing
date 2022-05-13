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
	/**
	 * @return array<int, string>
	 */
	public function getMethods(): array;
	/**
	 * @return array<string, string>
	 */
	public function getParams(): array;
}
