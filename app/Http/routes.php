<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$app->group(['middleware' => 'api'], function() use ($app) {
    // index action
    $app->get('/{resourceName:[A-Za-z-_]+}/', 'App\Http\Controllers\APIController@index');

    // store action
    $app->post('/{resourceName:[A-Za-z-_]+}/', 'App\Http\Controllers\APIController@store');

    // update action
    $app->put('/{resourceName:[A-Za-z-_]+}/{id:\d+}/', 'App\Http\Controllers\APIController@update');
    $app->patch('/{resourceName:[A-Za-z-_]+}/{id:\d+}/', 'App\Http\Controllers\APIController@update');

    // destroy action
    $app->delete('/{resourceName:[A-Za-z-_]+}/{id:\d+}/', 'App\Http\Controllers\APIController@destroy');

    // get action
    $app->get('/{resourceName:[A-Za-z-_]+}/{id:\d+}/', 'App\Http\Controllers\APIController@get');
});