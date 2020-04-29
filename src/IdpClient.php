<?php

namespace Helium\IdpClient;

use Helium\FriendlyApi\Contracts\FriendlyApiEngineContract;
use Helium\FriendlyApi\Exceptions\FriendlyApiException;
use Helium\FriendlyApi\FriendlyApi;
use Helium\FriendlyApi\Models\FriendlyApiResponse;
use Helium\IdpClient\Exceptions\Base\IdpException;
use Helium\IdpClient\Exceptions\IdpInvalidArgumentException;
use Helium\IdpClient\Exceptions\IdpMissingConfigurationException;
use Helium\IdpClient\Exceptions\IdpRemoteException;
use Helium\IdpClient\Exceptions\IdpResponseException;
use Helium\IdpClient\Models\IdpOrganization;
use Helium\IdpClient\Models\IdpPaginatedList;
use Helium\IdpClient\Models\IdpServerToken;
use Helium\IdpClient\Models\IdpUser;

/**
 * A note on Server Tokens:
 * Server tokens are retrieved once using the client id and secret, then are used
 * in the Authentication header of any future requests.
 * For the Helium IDP service, server tokens are very short-lived, and should not
 * be cached at the application level for reuse in other incoming requests to this
 * application. However, in the fractions of a second it takes to execute a single
 * user request to this application, there may be several requests to the IDP server,
 * in which case the same server token may be reused. Thus, upon the first request
 * to the IDP server, the server token is retrieved and stored as a static member of
 * this engine, which persists for reuse for the duration of the current request.
 * When the request exits, the singleton instance, and thus the stored server token,
 * will expire.
 */
class IdpClient
{
	//region Base
	protected static $serverToken;
	//endregion

	//region Helpers
	protected static function getBaseUrl(): string
	{
		$url = config('idp.baseUrl');

		if (!$url)
		{
			throw new IdpMissingConfigurationException('baseUrl');
		}

		return $url;
	}

	protected static function getClientId(): string
	{
		$id = config('idp.clientId');

		if (!$id)
		{
			throw new IdpMissingConfigurationException('clientId');
		}

		return $id;
	}

	protected static function getClientSecret(): string
	{
		$secret = config('idp.clientSecret');

		if (!$secret)
		{
			throw new IdpMissingConfigurationException('secret');
		}

		return $secret;
	}

	/**
	 * @description Get a FriendlyApi instance for making requests
	 * @return FriendlyApi
	 */
	protected static function getFriendlyApi(): FriendlyApiEngineContract
	{
		return FriendlyApi::create()
			->setBaseUrl(self::getBaseUrl());
	}

	/**
	 * @description Process FriendlyApiResponse object into an array of data
	 * Check response code, and throw an IdpException if not success
	 * @param FriendlyApiResponse $response
	 * @return array
	 * @throws IdpException
	 */
	protected static function processResponse(FriendlyApiResponse $response): array
	{
		if (!$response->isSuccessfulStatusCode())
		{
			$messages = $response->getJson()['message'] ?? '';

			if (is_string($messages))
			{
				$messages = [$messages];
			}

			$code = $response->getCode();

			throw new IdpResponseException($code, $messages);
		}

		return $response->getJson();
	}
	//endregion

	//region Contract
	public static function getServerToken(): IdpServerToken
	{
		if (self::$serverToken)
		{
			return self::$serverToken;
		}

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::POST)
				->setPath('v1/oauth/token')
				->setJsonQuery([
					'grant_type' => 'client_credentials',
					'client_id' => self::getClientId(),
					'client_secret' => self::getClientSecret(),
					'scope' => '*'
				])
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		$token = new IdpServerToken($data);

		self::$serverToken = $token;

		return $token;
	}

	/**
	 * @inheritDoc
	 */
	public static function createOrganization(
		IdpOrganization $organization): IdpOrganization
	{
		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::POST)
				->setPath('api/v1/organization')
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->setJsonQuery($organization->toArray())
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		return new IdpOrganization($data);
	}

	/**
	 * @inheritDoc
	 */
	public static function updateOrganization(string $organizationId,
		IdpOrganization $organization): IdpOrganization
	{
		if (empty($organizationId))
		{
			throw new IdpInvalidArgumentException(
				'updateOrganization',
				'Organization Id (string)',
				$organizationId);
		}

		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::PATCH)
				->setPath("api/v1/organization/{$organizationId}")
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->setJsonQuery($organization->toArray())
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		return new IdpOrganization($data);
	}

	/**
	 * @inheritDoc
	 */
	public static function registerUser(IdpUser $user): IdpUser
	{
		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::POST)
				->setPath('api/v1/user')
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->setJsonQuery($user->toArray())
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		return new IdpUser($data);
	}

	/**
	 * @inheritDoc
	 */
	public static function listUsers(int $page = 1): IdpPaginatedList
	{
		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::GET)
				->setPath('api/v1/users')
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->setQuery(['page' => $page])
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		$users = [];
		foreach ($data['data'] as $user)
		{
			$users[] = new IdpUser($user);
		}

		$data['data'] = $users;

		return new IdpPaginatedList($data);
	}

	/**
	 * @inheritDoc
	 */
	public static function getUser(string $userId): IdpUser
	{
		if (empty($userId))
		{
			throw new IdpInvalidArgumentException(
				'getUser',
				'User Id (string)',
				$userId);
		}

		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::GET)
				->setPath("api/v1/user/{$userId}")
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		return new IdpUser($data);
	}

	/**
	 * @inheritDoc
	 */
	public static function deleteUser(string $userId): void
	{
		if (empty($userId))
		{
			throw new IdpInvalidArgumentException(
				'deleteUser',
				'User Id (string)',
				$userId);
		}

		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::DELETE)
				->setPath("api/v1/user/{$userId}")
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);
	}

	/**
	 * @inheritDoc
	 */
	public static function associateUser(string $userId): IdpUser
	{
		if (empty($userId))
		{
			throw new IdpInvalidArgumentException(
				'associateUser',
				'User Id (string)',
				$userId);
		}

		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::PATCH)
				->setPath("api/v1/user/{$userId}/organization")
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		return new IdpUser($data);
	}

	/**
	 * @inheritDoc
	 */
	public static function associateUserToken(string $userToken): IdpUser
	{
		if (empty($userToken))
		{
			throw new IdpInvalidArgumentException(
				'associateUserToken',
				'User Token (string)',
				$userToken);
		}

		$serverToken = self::getServerToken();

		self::validateUserToken($userToken);

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::POST)
				->setPath('api/v1/user/organization/token')
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->setJsonQuery([
					'access_token' => $userToken
				])
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		return new IdpUser($data);
	}

	/**
	 * @inheritDoc
	 */
	public static function validateUserToken(string $userToken): IdpUser
	{
		if (empty($userToken))
		{
			throw new IdpInvalidArgumentException(
				'validateUserToken',
				'User Token (string)',
				$userToken);
		}

		$serverToken = self::getServerToken();

		try
		{
			$response = self::getFriendlyApi()
				->setMethod(FriendlyApi::POST)
				->setPath('api/v1/user/token')
				->addHeader('Authorization', $serverToken->getTokenHeaderValue())
				->setJsonQuery([
					'access_token' => $userToken
				])
				->send();
		}
		catch (FriendlyApiException $e)
		{
			throw new IdpRemoteException($e);
		}

		$data = self::processResponse($response);

		return new IdpUser($data);
	}
	//endregion
}