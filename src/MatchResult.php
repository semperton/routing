<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class MatchResult
{
	/** @var bool */
	protected $match;

	/** @var mixed */
	protected $handler;

	/** @var string[] */
	protected $methods;

	/** @var array */
	protected $params;

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

	public function getHandler()
	{
		return $this->handler;
	}

	public function getMethods(): array
	{
		return $this->methods;
	}

	public function getParams(): array
	{
		return $this->params;
	}
}
