<?php

declare(strict_types=1);

namespace Semperton\Routing\Tests;

use Semperton\Routing\Collection\RouteCollection;

class DummyCollection extends RouteCollection
{
	public array $routes = [];

	public function map(array $methods, string $path, $target, string $name = ''): self
	{
		parent::map($methods, $path, $target, $name);

		$path = $this->pathPrefix . $path;

		if ($name !== '') {
			$name = $this->namePrefix . $name;
		}

		foreach ($methods as $method) {
			$method = strtoupper($method);
			$this->routes[] = [$method, $path, $target, $name];
		}

		return $this;
	}
}
