<?php

namespace Helium\IdpClient\Middleware;

use Closure;
use Helium\IdpClient\Helpers\Jwt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class IdpScopeAll
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param ...$requiredScopes
     * @return mixed
     * @throws AuthorizationException
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
             * Check that every required scope is in the token's list of scopes
             */
            foreach ($requiredScopes as $scope)
            {
                if (!in_array($scope, $tokenScopes))
                {
                    /**
                     * This exception will always be caught in the "catch" block below. If app.debug is true, the same
                     * exception will be re-thrown, and thus this message will be visible. Otherwise, and a new generic
                     * Authorization exception will bet thrown.
                     */
                    $this->unauthorized('All of the following scopes are required: ' . implode(', ', $requiredScopes));
                }
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
