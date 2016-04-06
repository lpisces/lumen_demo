<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('/v1/user/login', ['uses' => 'UserController@login']);
$app->get('/v1/user/getyzm', ['uses' => 'UserController@getyzm']);
$app->get('/v1/user/regsave', ['uses' => 'UserController@regsave']);
$app->get('/v1/user/thirdpartylogin', ['uses' => 'UserController@thirdpartylogin']);
$app->get('/v1/user/bindingaccount', ['uses' => 'UserController@bindingaccount']);
$app->get('/v1/user/resetpwd', ['uses' => 'UserController@resetpwd']);
$app->get('/v1/user/personal', ['middleware' => 'auth', 'uses' => 'UserController@personal']);

$app->post('/v1/user/login', ['uses' => 'UserController@login']);
$app->post('/v1/user/getyzm', ['uses' => 'UserController@getyzm']);
$app->post('/v1/user/regsave', ['uses' => 'UserController@regsave']);
$app->post('/v1/user/thirdpartylogin', ['uses' => 'UserController@thirdpartylogin']);
$app->post('/v1/user/bindingaccount', ['uses' => 'UserController@bindingaccount']);
$app->post('/v1/user/resetpwd', ['uses' => 'UserController@resetpwd']);
$app->post('/v1/user/personal', ['middleware' => 'auth', 'uses' => 'UserController@personal']);



