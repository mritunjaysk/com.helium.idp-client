<?php

namespace Helium\IdpClient\Exceptions;

use Helium\IdpClient\Exceptions\Base\IdpException;
use Throwable;

class IdpRemoteException extends IdpException
{
	/**
	 * @description General exception for failed requests.
	 * IdpRemoteException should only be thrown as a wrapper around a previous
	 * exception, which is a required argument for debugging purposes. If an
	 * exception relating to the IDP service should be thrown without a previous
	 * exception, create a custom exception class.
	 * @param Throwable $previous
	 */
	public function __construct(Throwable $previous)
	{
		$message = "Could not complete your request. ";
		$message = "See previous exception for more information.";
		
		parent::__construct($message, 0, $previous);
	}
}