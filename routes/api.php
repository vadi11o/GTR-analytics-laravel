<?php

use App\Infrastructure\Controllers\RegisterUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Controllers\GetStreamsController;
use App\Infrastructure\Controllers\UserController;
use App\Infrastructure\Controllers\TopsofthetopsController;

Route::get('/users', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/streams', GetStreamsController::class);

Route::get('/streamer', UserController::class);

Route::get('/topsofthetops', TopsofthetopsController::class);

Route::post('/users', RegisterUserController::class);
