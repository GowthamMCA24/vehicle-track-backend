<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'store']);
Route::post('/login', [\App\Http\Controllers\Api\UserController::class, 'login']);
Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'getUser'])->middleware('auth:sanctum');
Route::post('/logout', [\App\Http\Controllers\Api\UserController::class, 'logout'])->middleware('auth:sanctum');
