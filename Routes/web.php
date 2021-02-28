<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
*/

use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\CheckStatus;
use App\Models\User;
use Illuminate\Support\Facades\Input;

Route::group(['middleware' => ['auth']], function () {

// only auth user...

    Route::get('/search', ['uses' => 'GithubController@finder', 'as' => 'search']);
    Route::get('/edit', ['uses' => 'FavoriteController@edit', 'as' => 'edit']);

    Route::get('/favorite', 'FavoriteController@index')->name('favorite');
    Route::delete('/delete/{id}', 'FavoriteController@delete')->name('delete');

});

Route::post('/login', function () {
    return view('login');
})->name('login');

// bonus)
Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

Auth::routes(['verify' => false]);
