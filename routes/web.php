<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/login', function () {
    return view('app'); // ou autre nom du blade si différent
})->name('login');

