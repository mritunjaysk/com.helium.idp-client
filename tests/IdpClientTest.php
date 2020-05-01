<?php

namespace Tests\Feature\IdentityProvider;

use Helium\IdpClient\Exceptions\IdpRemoteException;
use Helium\IdpClient\Exceptions\IdpResponseException;
use Helium\IdpClient\IdpClient;
use Helium\IdpClient\Models\IdpOrganization;
use Helium\IdpClient\Models\IdpPaginatedList;
use Helium\IdpClient\Models\IdpServerToken;
use Helium\IdpClient\Models\IdpUser;
use Exception;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class IdpClientTest extends TestCase
{
	/** @var \Illuminate\Http\Client\ResponseSequence */
	protected $sequence;

	protected function setUp(): void
	{
		parent::setUp();

		config([
			'idp.baseUrl' => 'http://example.com',
			'idp.clientId' => '123',
			'idp.clientSecret' => 'abc'
		]);

		$this->sequence = Http::fakeSequence();
	}

	protected function fakeSuccessfulRequest(array $data = [])
	{
		$this->sequence->push($data, 200);
	}

	protected function fakeUnsuccessfulRequest()
	{
		$this->sequence->push([], 500);
	}

	//region Tests
	public function testGetServerTokenUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$response = IdpClient::getServerToken();
			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testGetServerTokenException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			IdpClient::getServerToken();

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testGetServerToken()
	{
		$this->fakeSuccessfulRequest();

		$response = IdpClient::getServerToken();

		$this->assertInstanceOf(IdpServerToken::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'POST';
		});

		Http::assertSent(function (Request $request, Response $response) {
			$data = $request->data();
			return isset($data['grant_type'])
				&& isset($data['client_id'])
				&& isset($data['client_secret'])
				&& isset($data['scope']);
		});
	}

	public function testCreateOrganization()
	{
		$this->fakeSuccessfulRequest();

		$organization = new IdpOrganization();
		$response = IdpClient::createOrganization($organization);

		$this->assertInstanceOf(IdpOrganization::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'POST';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testCreateOrganizationUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$organization = new IdpOrganization();
			$response = IdpClient::createOrganization($organization);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testCreateOrganizationException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$organization = new IdpOrganization();
			$response = IdpClient::createOrganization($organization);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testUpdateOrganization()
	{
		$this->fakeSuccessfulRequest();

		$organization = new IdpOrganization();
		$response = IdpClient::updateOrganization('ORG-123', $organization);

		$this->assertInstanceOf(IdpOrganization::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'PATCH';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return Str::of($request->url())->contains('ORG-123');
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testUpdateOrganizationUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$organization = new IdpOrganization();
			$response = IdpClient::updateOrganization('ORG-123', $organization);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testUpdateOrganizationException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$organization = new IdpOrganization();
			$response = IdpClient::updateOrganization('ORG-123', $organization);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testRegisterUser()
	{
		$this->fakeSuccessfulRequest();

		$user = new IdpUser();
		$response = IdpClient::registerUser($user);

		$this->assertInstanceOf(IdpUser::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'POST';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testRegisterUserUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$user = new IdpUser();
			$response = IdpClient::registerUser($user);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testRegisterUserException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$user = new IdpUser();
			$response = IdpClient::registerUser($user);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testListUsers()
	{
		$this->fakeSuccessfulRequest([
			'data' => [
				[]
			]
		]);

		$response = IdpClient::listUsers();

		$this->assertInstanceOf(IdpPaginatedList::class, $response);
		$this->assertIsArray($response->data);

		foreach ($response->data as $datum)
		{
			$this->assertInstanceOf(IdpUser::class, $datum);
		}

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'GET';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testListUsersUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$response = IdpClient::listUsers();

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testListUsersException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$response = IdpClient::listUsers();

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testGetUser()
	{
		$this->fakeSuccessfulRequest();

		$user = new IdpUser();
		$response = IdpClient::getUser($user);

		$this->assertInstanceOf(IdpUser::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'GET';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testGetUserUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$user = new IdpUser();
			$response = IdpClient::getUser($user);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testGetUserException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$user = new IdpUser();
			$response = IdpClient::getUser($user);

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testDeleteUser()
	{
		$this->fakeSuccessfulRequest();

		$response = IdpClient::deleteUser('USR-123');

		$this->assertNull($response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'DELETE';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return Str::of($request->url())->contains('USR-123');
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testDeleteUserUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$response = IdpClient::deleteUser('USR-123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testDeleteUserException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$response = IdpClient::deleteUser('USR-123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testAssociateUser()
	{
		$this->fakeSuccessfulRequest();

		$response = IdpClient::associateUser('USR-123');

		$this->assertInstanceOf(IdpUser::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'PATCH';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return Str::of($request->url())->contains('USR-123');
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testAssociateUserUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$response = IdpClient::associateUser('USR-123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testAssociateUserException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$response = IdpClient::associateUser('USR-123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testAssociateUserToken()
	{
		$this->fakeSuccessfulRequest(); //Fake validateUserToken
		$this->fakeSuccessfulRequest(); //Fake associateUserToken

		$response = IdpClient::associateUserToken('abc123');

		$this->assertInstanceOf(IdpUser::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'POST';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return isset($request->data()['access_token']);
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testAssociateUserTokenUnsuccessful()
	{
		$this->fakeSuccessfulRequest(); //Fake validateUserToken
		$this->fakeUnsuccessfulRequest(); //Fake associateUserToken

		try
		{
			$response = IdpClient::associateUserToken('abc123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testAssociateUserTokenException()
	{
		$this->fakeSuccessfulRequest(); //Fake validateUserToken
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$response = IdpClient::associateUserToken('abc123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}

	public function testValidateUserToken()
	{
		$this->fakeSuccessfulRequest();

		$response = IdpClient::validateUserToken('abc123');

		$this->assertInstanceOf(IdpUser::class, $response);

		Http::assertSent(function (Request $request, Response $response) {
			return $request->method() == 'POST';
		});

		Http::assertSent(function (Request $request, Response $response) {
			return isset($request->data()['access_token']);
		});

		Http::assertSent(function (Request $request, Response $response) {
			return count($request->header('Authorization')) == 1;
		});
	}

	public function testValidateUserTokenUnsuccessful()
	{
		$this->fakeUnsuccessfulRequest();

		try
		{
			$response = IdpClient::validateUserToken('abc123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpResponseException::class, $e);
		}
	}

	public function testValidateUserTokenException()
	{
		//By not pushing a new response to the HTTP fake response sequence, an
		//exception will be thrown

		try
		{
			$response = IdpClient::validateUserToken('abc123');

			$this->assertTrue(false);
		}
		catch (Exception $e)
		{
			$this->assertInstanceOf(IdpRemoteException::class, $e);
		}
	}
	//endregion
}
