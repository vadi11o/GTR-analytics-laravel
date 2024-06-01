<?php

use App\Infrastructure\Controllers\RegisterUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Controllers\GetStreamsController;
use App\Infrastructure\Controllers\StreamerController;
use App\Infrastructure\Controllers\TopsofthetopsController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/streams', GetStreamsController::class);

Route::get('/streamers', StreamerController::class);

Route::get('/topsofthetops', TopsofthetopsController::class);
