<?php

namespace Helium\IdpClient\Models;

use Jenssegers\Model\Model as OfflineModel;

/**
 * @description This class represents the incoming data model for a paginated list
 * of data
 * 
 * @property array $data
 * @property string $path
 * @property string $first_page_url
 * @property string $next_page_url
 * @property string $prev_page_url
 * @property string $last_page_url
 * @property int $per_page
 * @property int $total
 * @property int $from
 * @property int $to
 * @property int $current_page
 * @property int $last_page
 */
class IdpPaginatedList extends OfflineModel
{
	protected $fillable = [
		'data',
		'path',
		'first_page_url',
		'next_page_url',
		'prev_page_url',
		'last_page_url',
		'per_page',
		'total',
		'from',
		'to',
		'current_page',
		'last_page'
	];

	public function __construct(array $attributes = [], ?string $type = null)
	{
		if (isset($attributes['data']) && !is_null($type))
		{
			$attributes['data'] = collect($attributes['data'])
				->map(fn($datum) => new $type($datum))
				->all();
		}

		parent::__construct($attributes);
	}
}