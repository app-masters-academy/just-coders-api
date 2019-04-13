<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

$router->get('/', function () use ($router) {
    return "API Root. Use /dev/ or /prod/.";
});

$envs = ['dev/', 'prod/'];

foreach ($envs as $env) {

    $router->get($env, function () use ($router, $env) {
        return "$env Root.";
    });

    // Users
    $router->group(['prefix' => $env . 'user', 'middleware' => 'jwt.auth'], function () use ($router) {
        $router->post('', ['uses' => 'UserController@create']);
        $router->get('', ['uses' => 'UserController@list']);
        $router->get('{id:[0-9]+}', ['uses' => 'UserController@read']);
        $router->delete('{id:[0-9]+}', ['uses' => 'UserController@delete']);
        $router->put('{id:[0-9]+}', ['uses' => 'UserController@update']);
        $router->get('{id:[0-9]+}/message', ['uses' => 'UserController@listPost']);
    });

    // Posts
    $router->group(['prefix' => $env . 'post', 'middleware' => 'jwt.auth'], function () use ($router) {
        $router->post('', ['uses' => 'PostController@create']);
        $router->get('', ['uses' => 'PostController@list']);
        $router->get('{id:[0-9]+}', ['uses' => 'PostController@read']);
        $router->delete('{id:[0-9]+}', ['uses' => 'PostController@delete']);
        $router->put('{id:[0-9]+}', ['uses' => 'PostController@update']);
    });

    //Auth
    $router->group(['prefix' => $env . 'auth'], function () use ($router) {
        $router->post('loginsocial', ['uses' => 'AuthController@loginSocial']);
        $router->post('githubcallback', ['uses' => 'AuthController@githubCallback']);
    });
}
