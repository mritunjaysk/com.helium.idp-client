<?php

namespace Helium\IdpClient;

use Helium\IdpClient\Exceptions\Base\IdpException;
use Helium\IdpClient\Exceptions\IdpMissingConfigurationException;
use Helium\IdpClient\Exceptions\IdpResponseException;
use Helium\IdpClient\Models\IdpOrganization;
use Helium\IdpClient\Models\IdpPaginatedList;
use Helium\IdpClient\Models\IdpAccessToken;
use Helium\IdpClient\Models\IdpUser;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ResponseSequence;
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

	protected static $users = [];

	protected static $orgs = [];

	protected static $fakeSequence;
	//endregion

	//region Testing
	public static function fake(callable $callback = null): Factory
	{
		return Http::fake($callback);
	}

	public static function fakeSequence(string $urlPattern = '*'): ResponseSequence
	{
		return Http::fakeSequence($urlPattern);
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
	/**
	 * @description Get Server Client token
	 * @return IdpAccessToken
	 * @throws IdpException
	 */
	public static function getServerToken(): IdpAccessToken
	{
		if (self::$serverToken)
		{
			return self::$serverToken;
		}

        $response = self::getHttp()
            ->asJson()
            ->post('v1/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => self::getClientId(),
                'client_secret' => self::getClientSecret(),
                'scope' => '*'
            ]);

		self::$serverToken = new IdpAccessToken(self::processResponse($response));;

		return self::$serverToken;
	}

	/**
	 * @description Create Organization (available for Admins only)
	 * @param IdpOrganization $organization
	 * @return IdpOrganization
	 * @throws IdpException
	 */
	public static function createOrganization(
		IdpOrganization $organization): IdpOrganization
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->post('api/v1/organization', $organization->toArray());

		$org = new IdpOrganization(self::processResponse($response));

		self::$orgs[$org->id] = $org;

		return $org;
	}

	/**
	 * @description List all organizations
	 * @return IdpPaginatedList
	 * @throws IdpException
	 */
	public static function listOrganizations(): IdpPaginatedList
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->get(
                "api/v1/organizations"
            );

		$list = new IdpPaginatedList(
			self::processResponse($response),
			IdpOrganization::class
		);

		foreach ($list->data as $org)
		{
			self::$orgs[$org->id] = $org;
		}

		return $list;
	}

	/**
	 * @description Retrieve the specified organization
	 * @param string $organizationId
	 * @return IdpOrganization
	 * @throws IdpException
	 */
	public static function getOrganization(string $organizationId): IdpOrganization
	{
		if (array_key_exists($organizationId, self::$orgs))
		{
			return self::$orgs[$organizationId];
		}

        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->get(
                "api/v1/organization/{$organizationId}"
            );

		$org = new IdpOrganization(self::processResponse($response));

		self::$orgs[$org->id] = $org;

		return $org;
	}


	/**
	 * @description Retrieve the current organization
	 * @return IdpOrganization
	 * @throws IdpException
	 */
	public static function getMyOrganization(): IdpOrganization
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->get(
                "api/v1/organization/me"
            );

		return new IdpOrganization(self::processResponse($response));
	}

	/**
	 * @description Update Organization (available for Admins and the current org
	 * only)
	 * @param string $organizationId
	 * @param IdpOrganization $organization
	 * @return IdpOrganization
	 * @throws IdpException
	 */
	public static function updateOrganization(string $organizationId,
		IdpOrganization $organization): IdpOrganization
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->patch(
                "api/v1/organization/{$organizationId}",
                $organization->toArray()
            );

		$org = new IdpOrganization(self::processResponse($response));

		self::$orgs[$org->id] = $org;

		return $org;
	}

	/**
	 * @description Register new user with the current org
	 * @param IdpUser $user
	 * @return IdpUser
	 * @throws IdpException
	 */
	public static function registerUser(IdpUser $user): IdpUser
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->post('api/v1/user', $user->toArray());

		$user = new IdpUser(self::processResponse($response));

		self::$users[$user->id] = $user;

		return $user;
	}

	/**
	 * @description List all users for the current organization
	 * @param int $page
	 * @return IdpPaginatedList
	 * @throws IdpException
	 */
	public static function listUsers(int $page = 1): IdpPaginatedList
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->get('api/v1/users', ['page' => $page]);

		$list = new IdpPaginatedList(
			self::processResponse($response),
			IdpUser::class
		);

		foreach ($list->data as $user)
		{
			self::$users[$user->id] = $user;
		}

		return $list;
	}

	/**
	 * @description Get user's info for this organization
	 * @param string $userId
	 * @return IdpUser
	 * @throws IdpException
	 */
	public static function getUser(string $userId): IdpUser
	{
		if (array_key_exists($userId, self::$users))
		{
			return self::$users[$userId];
		}

        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->get("api/v1/user/{$userId}");

		$user = new IdpUser(self::processResponse($response));

		self::$users[$user->id] = $user;

		return $user;
	}

	/**
	 * @description Disassociate the specified user from the current organization
	 * @param string $userId
	 * @throws IdpException
	 */
	public static function deleteUser(string $userId): void
	{
		$serverToken = self::getServerToken();

        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->delete("api/v1/user/{$userId}");

		self::processResponse($response);
	}

	/**
	 * @description Associate an existing user with the current organization
	 * @param string $userId
	 * @return IdpUser
	 * @throws IdpException
	 */
	public static function associateUser(string $userId): IdpUser
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->patch("api/v1/user/{$userId}/organization");

		$user = new IdpUser(self::processResponse($response));

		self::$users[$user->id] = $user;

		return $user;
	}

	/**
	 * @description Associate an existing user with the current organization
	 * @param string $userToken
	 * @return IdpUser
	 * @throws IdpException
	 */
	public static function associateUserToken(string $userToken): IdpUser
	{
		self::validateUserToken($userToken);

        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->post('api/v1/user/organization/token', [
                'access_token' => $userToken
            ]);

		$user = new IdpUser(self::processResponse($response));

		self::$users[$user->id] = $user;

		return $user;
	}

	/**
	 * @description In development environments only, retrieve an active user token
	 * for testing authenticated endpoints
	 * @param string $userId
	 * @return IdpAccessToken
	 */
	public static function getDevUserToken(string $userId): IdpAccessToken
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->get("api/v1/user/$userId/token");

		return new IdpAccessToken(self::processResponse($response));
	}

	/**
	 * @description Validate the given user token is active
	 * @param string $userToken
	 * @return IdpUser
	 * @throws IdpException
	 */
	public static function validateUserToken(string $userToken, string $accessToken = ''): IdpUser
	{
		$accessToken = empty($accessToken) ? self::getServerToken()->access_token : $accessToken;
		$response = self::getHttp()
	            ->withToken($accessToken)
	            ->asJson()
	            ->post('api/v1/user/token', [
	                'access_token' => $userToken
	            ]);

		$user = new IdpUser(self::processResponse($response));

		self::$users[$user->id] = $user;

		return $user;
	}

	/**
	 * @description Impersonate the specified user (available to Admins only)
	 * @param string $userId
	 * @param string $userToken Current admin user's token
	 * @return IdpAccessToken
	 * @throws IdpException
	 */
	public static function impersonateUser(string $userId,
		string $userToken): IdpAccessToken
	{
        $response = self::getHttp()
            ->withToken(self::getServerToken()->access_token)
            ->asJson()
            ->post("api/v1/user/{$userId}/impersonate", [
                'requesting_access_token' => $userToken
            ]);

		return new IdpAccessToken(self::processResponse($response));
	}
	//endregion
}
