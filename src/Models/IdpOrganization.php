<?php

namespace Helium\IdpClient\Models;

use Jenssegers\Model\Model as OfflineModel;

/**
 * @description This class represents both the incoming and outgoing data models
 * for an Organization on the IDP Server
 *
 * @property string $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $name
 * @property string $route
 * @property bool $self_hosted
 * @property bool $active
 * @property string $client_id
 * @property string $client_secret
 */
class IdpOrganization extends OfflineModel
{
	protected $fillable = [
		'id',
		'created_at',
		'updated_at',
		'name',
		'route',
		'self_hosted',
		'active',
		'client_id',
		'client_secret'
	];
}