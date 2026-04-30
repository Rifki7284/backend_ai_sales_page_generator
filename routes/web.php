<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SalesPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return response()->json('hi');
});
Route::prefix('api/v2')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', fn (Request $request) => $request->user());
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Sales Pages Resource
        Route::prefix('pages')->group(function () {
            Route::get('/', [SalesPageController::class, 'index']);
            Route::post('/generate', [SalesPageController::class, 'generate']);
            Route::get('/{id}', [SalesPageController::class, 'show']);
            Route::put('/{id}', [SalesPageController::class, 'update']);
            Route::delete('/{id}', [SalesPageController::class, 'destroy']);
        });
    });
});
