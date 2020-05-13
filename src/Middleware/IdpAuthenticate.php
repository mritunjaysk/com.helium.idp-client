<?php

namespace Helium\IdpClient\Middleware;

use Helium\IdpClient\Exceptions\Base\IdpException;
use Helium\IdpClient\IdpClient;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;

class IdpAuthenticate
{
	/**
	 * @description Authentication failed, throw AuthenticationException
	 * @throws AuthenticationException
	 */
	protected function unauthenticated()
	{
		throw new AuthenticationException('Failed to authenticate user.');
	}

    /**
     * @description Authenticate incoming requests with IDP
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next)
    {
	    /**
	     * Get the authorization token from the incoming request
	     */
    	$authToken = $request->bearerToken();

    	try
	    {
		    /**
		     * Validate token against IDP Service
		     */
		    $idpUser = IdpClient::validateUserToken($authToken);

		    /**
		     * Token validated successfully, IdpUser object returned.
		     * Try to set the auth user for this request only using the given
		     * IdpUser. If the user does not exist, this line returns false.
		     */
		    $authUser = Auth::onceUsingId($idpUser->id);

		    /**
		     * If the IdpUser token validates successfully, but the user does not
		     * exist in this system, they should not be authenticated.
		     */
		    if (!$authUser)
		    {
		    	$this->unauthenticated();
		    }
	    }
	    catch (\Throwable $e)
	    {
		    /**
		     * If anything goes wrong, including a failed request to the Idp, an
		     * unsuccessful response code, or some other general error, the user
		     * should not be authenticated.
		     */
	    	$this->unauthenticated();
	    }

        return $next($request);
    }
}
