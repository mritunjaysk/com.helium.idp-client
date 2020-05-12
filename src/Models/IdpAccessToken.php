<?php

namespace Helium\IdpClient\Models;

use Jenssegers\Model\Model as OfflineModel;

/**
 * @description This class represents both the incoming data model for a Server
 * Token provided by the IDP Server for use in the Authorization header of other
 * requests
 *
 * @property string $token_type
 * @property int $expires_in
 * @property string $access_token
 */
class IdpAccessToken extends OfflineModel
{
	protected $attributes = [
		'token_type' => 'Bearer'
	];

	protected $fillable = [
		'token_type',
		'expires_in',
		'access_token'
	];
}