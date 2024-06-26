<?php

use App\Infrastructure\Controllers\TimelineController;
use App\Infrastructure\Controllers\FollowStreamerController;
use App\Infrastructure\Controllers\UnfollowStreamerController;
use App\Infrastructure\Controllers\RegisterUserController;
use App\Infrastructure\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Infrastructure\Controllers\GetStreamsController;
use App\Infrastructure\Controllers\StreamerController;
use App\Infrastructure\Controllers\TopsofthetopsController;

Route::get('/users', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/follow', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/streams', GetStreamsController::class);

Route::get('/streamers', StreamerController::class);

Route::get('/topsofthetops', TopsofthetopsController::class);

Route::post('/users', RegisterUserController::class);

Route::get('/users', UserController::class);

Route::post('/follow', FollowStreamerController::class);

Route::delete('/unfollow', UnfollowStreamerController::class);

Route::get('/timeline', TimelineController::class);

