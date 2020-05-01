<?php

namespace Helium\IdpClient\Providers;

use Helium\IdpClient\Middleware\IdpAuthenticate;
use Illuminate\Support\ServiceProvider;

class IdpServiceProvider extends ServiceProvider
{
	/**
	 * Register any application Services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/idp.php', 'idp');

		app('router')->aliasMiddleware('idp-auth', IdpAuthenticate::class);
	}

	/**
	 * Bootstrap any application Services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/idp.php' => config_path('idp.php'),
		]);
	}
}