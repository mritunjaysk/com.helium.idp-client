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

		$exceptionMessage = "Request to IDP Server failed with status code $httpStatusCode." . PHP_EOL;
		$exceptionMessage .= "Messages from server:" . PHP_EOL;
		$exceptionMessage .= print_r($messages, true);

		parent::__construct($exceptionMessage);
	}

	/**
	 * @description Get HTTP Status Code of the failed request
	 * @return int
	 */
	public function status(): int
	{
		return $this->httpStatusCode;
	}

	/**
	 * @description Get response messages
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->messages;
	}
}