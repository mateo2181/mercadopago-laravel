<?php

Route::group(['middleware' => ['web']], function () {
    Route::prefix('mercadopago')->group(function () {

        Route::get('/generateUser', 'Laravel\Mercadopago\Http\Controllers\MPController@generateUser');
        
        Route::get('/redirect', 'Laravel\Mercadopago\Http\Controllers\MPController@createPayment')->name('mercadopago.redirect');
        Route::get('/success', 'Laravel\Mercadopago\Http\Controllers\MPController@success')->name('mercadopago.success');
        Route::get('/failure', 'Laravel\Mercadopago\Http\Controllers\MPController@failure')->name('mercadopago.failure');
        Route::get('/pending', 'Laravel\Mercadopago\Http\Controllers\MPController@pending')->name('mercadopago.pending');
        Route::post('/ipn', 'Laravel\Mercadopago\Http\Controllers\MPController@ipn')->name('mercadopago.ipn');

        Route::get('/orderMP/{id}', 'Laravel\Mercadopago\Http\Controllers\MPController@cancelOrder');

    });
});