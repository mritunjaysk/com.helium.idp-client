<?php

namespace Helium\IdpClient\Exceptions;

use InvalidArgumentException;

class IdpInvalidArgumentException extends InvalidArgumentException
{
	/**
	 * @description Enforce valid arguments are provided for all IDP calls
	 * @param string $method
	 * @param string $expected
	 * @param string $actual
	 */
	public function __construct(string $method, string $expected, string $actual)
	{
		$message = "Invalid argument supplied to {$method}. ";
		$message = "Expected {$expected}, got '{$actual}'";

		parent::__construct($message);
	}
}