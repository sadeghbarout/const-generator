<?php

namespace Colbeh\Consts;


class ServiceProvider extends \Illuminate\Support\ServiceProvider {
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		include __DIR__ . '/routes.php';

		$this->loadViewsFrom(__DIR__ . '/Views', 'builder');

		$this->publishes([
			__DIR__.'/consts.php' => app_path('Extras/consts.php'),
		], 'app');

	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
//		$this->app['bmi'] = $this->app->share(function ($app) {
//			return new BMI;
//		});
	}
}
