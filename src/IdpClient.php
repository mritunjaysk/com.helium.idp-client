<?php

namespace Helium\IdpClient;

use Helium\IdpClient\Exceptions\Base\IdpException;
use Helium\IdpClient\Exceptions\IdpInvalidArgumentException;
use Helium\IdpClient\Exceptions\IdpMissingConfigurationException;
use Helium\IdpClient\Exceptions\IdpRemoteException;
use Helium\IdpClient\Exceptions\IdpResponseException;
use Helium\IdpClient\Models\IdpOrganization;
use Helium\IdpClient\Models\IdpPaginatedList;
use Helium\IdpClient\Models\IdpServerToken;
use Helium\IdpClient\Models\IdpUser;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

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

	//region Testing
	public static function fake()
	{
		Http::fake(function() {
			return Http::response([], 200);
		});
	}
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

	protected static function getHttp(): PendingRequest
	{
		return Http::withOptions([
			'base_uri' => self::getBaseUrl()
		]);
	}

	/**
	 * @description Process response object into an array of data
	 * Check response code, and throw an IdpException if not success
	 * @param Response $response
	 * @return array
	 * @throws IdpException
	 */
	protected static function processResponse(Response $response): array
	{
		if (!$response->successful())
		{
			$messages = $response->json()['message'] ?? '';

			if (is_string($messages))
			{
				$messages = [$messages];
			}

			throw new IdpResponseException($response->status(), $messages);
		}

		return $response->json();
	}
	//endregion

	//region Calls
	public static function getServerToken(): IdpServerToken
	{
		if (self::$serverToken)
		{
			return self::$serverToken;
		}

		try
		{
			$response = self::getHttp()
				->asJson()
				->post('v1/oauth/token', [
					'grant_type' => 'client_credentials',
					'client_id' => self::getClientId(),
					'client_secret' => self::getClientSecret(),
					'scope' => '*'
				]);
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		self::$serverToken = new IdpServerToken(self::processResponse($response));;

		return self::$serverToken;
	}

	/**
	 * @inheritDoc
	 */
	public static function createOrganization(
		IdpOrganization $organization): IdpOrganization
	{
		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->asJson()
				->post('api/v1/organization', $organization->toArray());
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpOrganization(self::processResponse($response));
	}

	/**
	 * @inheritDoc
	 */
	public static function updateOrganization(string $organizationId,
		IdpOrganization $organization): IdpOrganization
	{
		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->asJson()
				->patch(
					"api/v1/organization/{$organizationId}",
					$organization->toArray()
				);
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpOrganization(self::processResponse($response));
	}

	/**
	 * @inheritDoc
	 */
	public static function registerUser(IdpUser $user): IdpUser
	{
		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->asJson()
				->post('api/v1/user', $user->toArray());
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpUser(self::processResponse($response));
	}

	/**
	 * @inheritDoc
	 */
	public static function listUsers(int $page = 1): IdpPaginatedList
	{
		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->get('api/v1/users', ['page' => $page]);
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpPaginatedList(
			self::processResponse($response),
			IdpUser::class
		);
	}

	/**
	 * @inheritDoc
	 */
	public static function getUser(string $userId): IdpUser
	{
		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->get("api/v1/user/{$userId}");
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpUser(self::processResponse($response));
	}

	/**
	 * @inheritDoc
	 */
	public static function deleteUser(string $userId): void
	{
		$serverToken = self::getServerToken();

		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->delete("api/v1/user/{$userId}");
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		self::processResponse($response);
	}

	/**
	 * @inheritDoc
	 */
	public static function associateUser(string $userId): IdpUser
	{
		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->patch("api/v1/user/{$userId}/organization");
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpUser(self::processResponse($response));
	}

	/**
	 * @inheritDoc
	 */
	public static function associateUserToken(string $userToken): IdpUser
	{
		self::validateUserToken($userToken);

		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->asJson()
				->post('api/v1/user/organization/token', [
					'access_token' => $userToken
				]);
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpUser(self::processResponse($response));
	}

	/**
	 * @inheritDoc
	 */
	public static function validateUserToken(string $userToken): IdpUser
	{
		try
		{
			$response = self::getHttp()
				->withToken(self::getServerToken()->access_token)
				->asJson()
				->post('api/v1/user/token', [
					'access_token' => $userToken
				]);
		}
		catch (\Exception $e)
		{
			throw new IdpRemoteException($e);
		}

		return new IdpUser(self::processResponse($response));
	}
	//endregion
}