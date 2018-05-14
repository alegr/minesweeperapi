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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// List all games
Route::get('/games', 'GamesController@index');
// Create new game
Route::post('/games', 'GamesController@create');
// Retrieve specific game
Route::get('/games/{id}', 'GamesController@show');
