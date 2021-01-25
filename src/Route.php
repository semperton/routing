<?php

declare(strict_types=1);

namespace Semperton\Routing;

final class Route
{
	/** @var string */
	public $method;

	/** @var string */
	public $path;

	/** @var mixed */
	public $handler;

	public function __construct(string $method, string $path, $handler)
	{
		$this->method = $method;
		$this->path = $path;
		$this->handler = $handler;
	}
}
