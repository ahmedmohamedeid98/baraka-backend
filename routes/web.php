<?php

use App\Http\Controllers\MediaProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/media/{path}', MediaProxyController::class)->where('path', '.*')->name('media.proxy');
