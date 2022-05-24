<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class MatchResult
{
	protected bool $match;

	/** @var null|mixed */
	protected $handler;

	/** @var array<int, string> */
	protected array $methods;

	/** @var array<string, string> */
	protected array $params;

	/**
	 * @param null|mixed $handler
	 * @param array<int, string> $methods
	 * @param array<string, string> $params
	 */
	public function __construct(bool $match, $handler = null, array $methods = [], array $params = [])
	{
		$this->match = $match;
		$this->handler = $handler;
		$this->methods = $methods;
		$this->params = $params;
	}

	public function isMatch(): bool
	{
		return $this->match;
	}

	/**
	 * @return null|mixed
	 */
	public function getHandler()
	{
		return $this->handler;
	}

	/**
	 * @return array<int, string>
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
