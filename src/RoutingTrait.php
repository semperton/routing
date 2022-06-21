<?php

declare(strict_types=1);

namespace Semperton\Routing;

use function trim;
use function explode;
use function is_array;
use function var_export;
use function implode;
use function is_object;
use function chr;
use function get_class;
use function strpos;
use function substr;
use function strlen;

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

	/**
	 * @param mixed $value
	 */
	protected function export($value): string
	{
		if (is_array($value)) {

			$mapped = [];

			/** @var mixed */
			foreach ($value as $key => $val) {
				$mapped[] = var_export($key, true) . '=>' . $this->export($val);
			}

			return '[' . implode(',', $mapped)  . ']';
		}

		if ($value instanceof \stdClass) {

			return '(object)' . $this->export((array)$value);
		}

		if (is_object($value)) {

			$nchr = chr(0);
			$classname = get_class($value);
			$protected = $nchr . '*' . $nchr;
			$private = $nchr . $classname . $nchr;

			/** @var array<string, mixed> */
			$props = (array)$value;

			/** @var mixed $val */
			foreach ($props as $name => $val) {

				$newname = $name;

				if (strpos($name, $protected) === 0) {
					$newname = substr($name, strlen($protected));
				} else if (strpos($name, $private) === 0) {
					$newname = substr($name, strlen($private));
				}

				unset($props[$name]);

				/** @var array<string, mixed> */
				$props[$newname] = $val;
			}

			return $classname . '::__set_state(' . $this->export($props) . ')';
		}

		return var_export($value, true);
	}
}
