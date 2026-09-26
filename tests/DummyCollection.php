<?php

declare(strict_types=1);

namespace Semperton\Routing\Tests;

use Semperton\Routing\Collection\RouteCollection;

class DummyCollection extends RouteCollection
{
	public array $routes = [];

	public function map(array $methods, string $path, mixed $handler, string $name = ''): static
	{
		parent::map($methods, $path, $handler, $name);

		$path = $this->pathPrefix . $path;

		if ($name !== '') {
			$name = $this->namePrefix . $name;
		}

		foreach ($methods as $method) {
			$this->routes[] = [$method, $path, $handler, $name];
		}

		return $this;
	}
}
