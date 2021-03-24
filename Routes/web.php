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

Route::prefix('order')->name('order.')->group(function () {
        Route::get('/', 'OrderController@index')->name('index');
        Route::post('/', 'OrderController@store')->name('store');
        Route::prefix('{id}')->group(function () {
            Route::get('/', 'OrderController@show')->name('show');
            Route::put('/', 'OrderController@update')->name('update');
            Route::get('edit', 'OrderController@edit')->name('edit');
        });

        Route::get('/new', 'OrderController@newOrders')->name('new');
        Route::get('/ajax', 'OrderController@getUpdatedOrderTable')->name('ajax-table');
});

Route::prefix('orders')->name('orders.')->group(function () {
    Route::post('status/{status}', 'OrderController@changeStatus')->name('status');
    Route::post('merge', 'OrderController@merge')->name('merge');
});

Route::prefix('item')->name('item.')->group(function () {
    Route::post('/', 'ItemController@store')->name('store');

    Route::prefix('{id}')->group(function () {
        Route::delete('/', 'ItemController@destroy')->name('destroy');
        Route::put('/', 'ItemController@update')->name('update');
        Route::get('service', 'ItemController@service')->name('service');
    });
});

Route::post('/login', function () {
    return view('login');
})->name('login');

// bonus)
Route::get('logout', '\App\Http\Controllers\Auth\LoginController@logout');

Auth::routes(['verify' => false]);
