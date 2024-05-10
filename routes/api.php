<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Controllers\StreamsController;
use App\Infrastructure\Controllers\UserController;
use App\Infrastructure\Controllers\TopsofthetopsController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/streams', StreamsController::class);

Route::get('/users', UserController::class);

Route::get('/topsofthetops', [TopsofthetopsController::class, 'index']);
