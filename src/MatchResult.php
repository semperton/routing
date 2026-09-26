<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class MatchResult
{
	/**
	 * @param list<string> $methods
	 * @param array<string, string> $params
	 */
	public function __construct(
		private readonly bool $match,
		private readonly mixed $handler = null,
		private readonly array $methods = [],
		private readonly array $params = []
	) {
	}

	public function isMatch(): bool
	{
		return $this->match;
	}

	public function getHandler(): mixed
	{
		return $this->handler;
	}

	/**
	 * @return list<string>
	 */
	public function getMethods(): array
	{
		return $this->methods;
	}

	/**
	 * @return array<string, string>
	 */
	public function getParams(): array
	{
		return $this->params;
	}
}
