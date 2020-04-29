<?php

namespace Tests\Feature\IdentityProvider;

use Helium\FriendlyApi\Exceptions\FriendlyApiException;
use Helium\FriendlyApi\FriendlyApi;
use Helium\FriendlyApi\Models\FriendlyApiResponse;
use Helium\IdpClient\Exceptions\IdpRemoteException;
use Helium\IdpClient\Exceptions\IdpResponseException;
use Helium\IdpClient\IdpClient;
use Helium\IdpClient\Models\IdpOrganization;
use Helium\IdpClient\Models\IdpPaginatedList;
use Helium\IdpClient\Models\IdpServerToken;
use Helium\IdpClient\Models\IdpUser;
use Exception;
use Orchestra\Testbench\TestCase;

class IdpClientTest extends TestCase
{
	//region Tests
//	public function testGetServerToken()
//	{
//		$this->mockGetServerToken();
//		$engine = $this->getInstance();
//
//		$response = $engine->getServerToken();
//
//		$this->assertInstanceOf(IdpServerToken::class, $response);
//	}
//
//	public function testGetServerTokenUnsuccessful()
//	{
//		$this->mockGetServerTokenUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->getServerToken();
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testGetServerTokenException()
//	{
//		$this->mockGetServerTokenException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->getServerToken();
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testCreateOrganization()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequest();
//		$engine = $this->getInstance();
//
//		$organization = new IdpOrganization();
//		$response = $engine->createOrganization($organization);
//
//		$this->assertInstanceOf(IdpOrganization::class, $response);
//	}
//
//	public function testCreateOrganizationUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$organization = new IdpOrganization();
//			$response = $engine->createOrganization($organization);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testCreateOrganizationException()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$organization = new IdpOrganization();
//			$response = $engine->createOrganization($organization);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testUpdateOrganization()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequest();
//		$engine = $this->getInstance();
//
//		$organization = new IdpOrganization();
//		$response = $engine->updateOrganization('ORG-123', $organization);
//
//		$this->assertInstanceOf(IdpOrganization::class, $response);
//	}
//
//	public function testUpdateOrganizationUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$organization = new IdpOrganization();
//			$response = $engine->updateOrganization('ORG-123', $organization);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testUpdateOrganizationException()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$organization = new IdpOrganization();
//			$response = $engine->updateOrganization('ORG-123', $organization);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testRegisterUser()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequest();
//		$engine = $this->getInstance();
//
//		$user = new IdpUser();
//		$response = $engine->registerUser($user);
//
//		$this->assertInstanceOf(IdpUser::class, $response);
//	}
//
//	public function testRegisterUserUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$user = new IdpUser();
//			$response = $engine->registerUser($user);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testRegisterUserException()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$user = new IdpUser();
//			$response = $engine->registerUser($user);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testListUsers()
//	{
//		$user = new IdpUser();
//		$this->mockGetServerToken();
//		$this->mockQueryRequest(json_encode([
//			'data' => [
//				$user->toArray()
//			]
//		]));
//		$engine = $this->getInstance();
//
//		$response = $engine->listUsers();
//
//		$this->assertInstanceOf(IdpPaginatedList::class, $response);
//		$this->assertIsArray($response->data);
//
//		foreach ($response->data as $datum)
//		{
//			$this->assertInstanceOf(IdpUser::class, $datum);
//		}
//	}
//
//	public function testListUsersUnsuccessful()
//	{
//		$user = new IdpUser();
//		$this->mockGetServerToken();
//		$this->mockQueryRequestUnsuccessful(json_encode([
//			'data' => [
//				$user->toArray()
//			]
//		]));
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->listUsers();
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testListUsersException()
//	{
//		$user = new IdpUser();
//		$this->mockGetServerToken();
//		$this->mockQueryRequestException(json_encode([
//			'data' => [
//				$user->toArray()
//			]
//		]));
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->listUsers();
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testGetUser()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequest();
//		$engine = $this->getInstance();
//
//		$user = new IdpUser();
//		$response = $engine->getUser($user);
//
//		$this->assertInstanceOf(IdpUser::class, $response);
//	}
//
//	public function testGetUserUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequestUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$user = new IdpUser();
//			$response = $engine->getUser($user);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testGetUserException()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequestException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$user = new IdpUser();
//			$response = $engine->getUser($user);
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testDeleteUser()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequest();
//		$engine = $this->getInstance();
//
//		$response = $engine->deleteUser('USR-123');
//
//		$this->assertNull($response);
//	}
//
//	public function testDeleteUserUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequestUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->deleteUser('USR-123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testDeleteUserException()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequestException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->deleteUser('USR-123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testAssociateUser()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequest();
//		$engine = $this->getInstance();
//
//		$response = $engine->associateUser('USR-123');
//
//		$this->assertInstanceOf(IdpUser::class, $response);
//	}
//
//	public function testAssociateUserUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequestUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->associateUser('USR-123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testAssociateUserException()
//	{
//		$this->mockGetServerToken();
//		$this->mockRequestException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->associateUser('USR-123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testAssociateUserToken()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequest(); //Mock validateUserToken
//		$this->mockJsonRequest(); //Mock associateUserToken
//		$engine = $this->getInstance();
//
//		$response = $engine->associateUserToken('abc123');
//
//		$this->assertInstanceOf(IdpUser::class, $response);
//	}
//
//	public function testAssociateUserTokenUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequest(); //Mock validateUserToken
//		$this->mockJsonRequestUnsuccessful(); //Mock associateUserToken
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->associateUserToken('abc123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testAssociateUserTokenException()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequest(); //Mock validateUserToken
//		$this->mockJsonRequestException(); //Mock associateUserToken
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->associateUserToken('abc123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
//
//	public function testValidateUserToken()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequest();
//		$engine = $this->getInstance();
//
//		$response = $engine->validateUserToken('abc123');
//
//		$this->assertInstanceOf(IdpUser::class, $response);
//	}
//
//	public function testValidateUserTokenUnsuccessful()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestUnsuccessful();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->validateUserToken('abc123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpResponseException::class, $e);
//		}
//	}
//
//	public function testValidateUserTokenException()
//	{
//		$this->mockGetServerToken();
//		$this->mockJsonRequestException();
//		$engine = $this->getInstance();
//
//		try
//		{
//			$response = $engine->validateUserToken('abc123');
//
//			$this->assertTrue(false);
//		}
//		catch (Exception $e)
//		{
//			$this->assertInstanceOf(IdpRemoteException::class, $e);
//		}
//	}
	//endregion
}
