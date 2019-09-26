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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::get('make/friends', 'FriendController@make_friend');
Route::get('friend/lists','FriendController@friend_lists');
Route::get('common/friend/lists','FriendController@common_friend_lists');
Route::get('subscribe','FriendController@subscribe');
