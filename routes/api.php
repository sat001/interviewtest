<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

    Route::post('/register', ['as' => 'register', 'uses' => 'api\v1\Registration\RegistrationController@signup']);
    Route::post('/login', ['as' => 'login', 'uses' => 'api\v1\Registration\RegistrationController@signin']);
    Route::post('/forget-password', ['as' => 'forget_password', 'uses' => 'api\v1\Registration\RegistrationController@forget_password']);
    Route::post('/check-otp', ['as' => 'check_otp', 'uses' => 'api\v1\Registration\RegistrationController@check_otp']);

