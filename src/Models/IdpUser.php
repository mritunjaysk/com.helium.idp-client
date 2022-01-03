<?php

namespace Helium\IdpClient\Models;

use Jenssegers\Model\Model as OfflineModel;

/**
 * @description This class represents both the incoming and outgoing data models
 * for a User on the IDP Server
 *
 * @property string $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $username
 * @property string $password
 * @property string $password_confirmation
 * @property string $pin
 * @property string $email
 * @property string $phone
 * @property string $language
 * @property string $country
 * @property array $organizations
 */
class IdpUser extends OfflineModel
{
	protected $fillable = [
		'id',
		'created_at',
		'updated_at',
		'username',
		'firstname',
		'lastname',
		'password',
		'password_confirmation',
		'pin',
		'email',
		'phone',
		'language',
		'country',
		'organizations',
		'last_login_time'
	];
}
