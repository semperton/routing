<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class MatchResult
{
	/** @var bool */
	protected $match;

	/** @var mixed */
	protected $handler;

	/** @var array<int, string> */
	protected $methods;

	/** @var array<string, scalar> */
	protected $params;

	/**
	 * @param mixed $handler
	 * @param array<int, string> $methods
	 * @param array<string, scalar> $params
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
	 * @return mixed
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
	 * @return array<string, scalar>
	 */
	public function getParams(): array
	{
		return $this->params;
	}
}
