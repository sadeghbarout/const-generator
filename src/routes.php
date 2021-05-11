<?php
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Colbeh\Consts\Controllers', 'prefix' => 'const'], function () {
//	Route::get('/', ['as' => 'bmi_path', 'uses' => 'ConstController@index']);

	Route::get('/', "ConstController@index");
	Route::post('/add', "ConstController@add");
	Route::post('/column', "ConstController@columnAdd");
});