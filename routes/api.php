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
// Mark a cell of a specific active game as flag
Route::post('/games/{id}/flag/{x}-{y}', 'GamesController@setDisplay');
// Mark a cell of a specific active game as question mark
Route::post('/games/{id}/questionmark/{x}-{y}', 'GamesController@setDisplay');
