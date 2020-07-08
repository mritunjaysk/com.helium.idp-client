<?php

namespace Helium\IdpClient\Middleware;

use Closure;
use Helium\IdpClient\Helpers\Jwt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class IdpScope
{
	/**
	 * @description Authentication failed, throw AuthenticationException
	 * @throws AuthenticationException
	 */
	protected function unauthorized(string $message = null)
	{
		$message = $message ?? 'You are not authorized to access this resource.';
		throw new AuthorizationException($message);
	}

    /**
     * @description Authenticate incoming requests with IDP
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param ...$requiredScopes
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, ...$requiredScopes)
    {
	    /**
	     * Get the authorization token from the incoming request
	     */
    	$authToken = $request->bearerToken();
    	if (empty($authToken))
	    {
	    	$this->unauthorized('No auth token provided');
	    }

    	try
	    {
            /**
             * Decode the JWT payload
             */
            $payload = Jwt::of($authToken)->payload();
            $tokenScopes = $payload['scopes'];

            /**
             * Check that at least one of the required scopes is in the token's list of scopes
             */
            $authorized = false;
            foreach ($requiredScopes as $scope)
            {
                /**
                 * If one required scope is found, automatically succeed
                 */
                if (in_array($scope, $tokenScopes))
                {
                    $authorized = true;
                    break;
                }
            }

            if (!$authorized)
            {
                /**
                 * This exception will always be caught in the "catch" block below. If app.debug is true, the same
                 * exception will be re-thrown, and thus this message will be visible. Otherwise, and a new generic
                 * Authorization exception will bet thrown.
                 */
                $this->unauthorized('One of the following scopes is required: ' . implode(', ', $requiredScopes));
            }
	    }
	    catch (\Throwable $e)
	    {
	    	if (config('app.debug'))
		    {
		    	throw $e;
		    }

		    /**
		     * If anything goes wrong, including a failed request to the Idp, an
		     * unsuccessful response code, or some other general error, the user
		     * should not be authenticated.
		     */
	    	$this->unauthorized();
	    }

        return $next($request);
    }
}
