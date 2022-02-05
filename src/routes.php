<?php
use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Colbeh\Consts\Controllers', 'prefix' => 'builder'], function () {
//	Route::get('/', ['as' => 'bmi_path', 'uses' => 'ConstController@index']);

	Route::get('/', "ConstController@index");
	Route::get('/const/table', "ConstController@index");
	Route::get('/const/column', "ConstController@column");
	Route::post('/const/add', "ConstController@add");
	Route::post('/const/column', "ConstController@columnAdd");

    Route::get('/vue', "VueBuilderController@index");
    Route::post('/vue/create', "VueBuilderController@vueCreate");

});



