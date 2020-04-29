<?php

namespace Helium\IdpClient\Exceptions;

use Helium\IdpClient\Exceptions\Base\IdpException;

class IdpMissingConfigurationException extends IdpException
{
	/**
	 * @description Enforce ClientId and ClientSecret configuration options set
	 */
	public function __construct(string $option)
	{
		$message = "Missing '{$option}' configuration option. ";
		$message .= "See config/idp.php. If your project does not contain the ";
		$message .= "idp.php config file, try php artisan vendor:publish.";

		parent::__construct($message);
	}
}