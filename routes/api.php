<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TopsofthetopsController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/streams', [StreamsController::class, 'index']);

Route::get('/users', [UserController::class, 'show']);

Route::get('/topsofthetops', [TopsofthetopsController::class, 'index']);
