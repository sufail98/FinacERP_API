<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/store-session', function (Request $request) {
    return view('loginForApp', [
        'token' => $request->query('token'),
        'db'    => $request->query('db'),
    ]);
})->name('store.session');