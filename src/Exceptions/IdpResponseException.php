<?php

namespace Helium\IdpClient\Exceptions;

use Helium\IdpClient\Exceptions\Base\IdpException;
use Throwable;

class IdpResponseException extends IdpException
{
	protected $httpStatusCode;
	protected $messages;

	/**
	 * @description Exception for responses with non-successful status codes. The
	 * request was sent successfully, but the server responded with an error.
	 * @param int $httpStatusCode
	 * @param array $messages
	 */
	public function __construct(int $httpStatusCode, array $messages)
	{
		$this->httpStatusCode = $httpStatusCode;
		$this->messages = $messages;

		$exceptionMessage = "IDP Request Failed. See ";
		$exceptionMessage .= "IdpResponseException::getMessages ";
		$exceptionMessage .= "for more information";

		parent::__construct($exceptionMessage);
	}

	/**
	 * @description Get HTTP Status Code of the failed request
	 * @return int
	 */
	public function getHttpStatusCode(): int
	{
		return $this->httpStatusCode;
	}

	/**
	 * @description Get response messages
	 * @return array
	 */
	public function getMessages(): array
	{
		return $this->messages;
	}
}