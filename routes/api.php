<?php

use App\Application\Http\Controllers\V1\AuthController;
use App\Application\Http\Controllers\V1\CartonBoxController;
use App\Application\Http\Controllers\V1\ValidationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;


Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
            $user = \App\Models\User::findOrFail($id);
            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                throw ValidationException::withMessages(['email' => 'Invalid verification link']);
            }
            if ($user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email already verified']);
            }
            $user->markEmailAsVerified();
            return response()->json(['message' => 'Email verified']);
        })->middleware('signed')->name('verification.verify');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});

Route::prefix('v1')->group(function () {

    Route::get('/carton-boxes', [CartonBoxController::class, 'index']);
    Route::post('/carton-boxes/{id}/process', [CartonBoxController::class, 'process']);
    Route::get('/carton-boxes/po', [CartonBoxController::class, 'getPOs']);
    Route::get('/carton-boxes/sku', [CartonBoxController::class, 'getSKUs']);
    Route::post('/carton-boxes/{id}/validate-item', [CartonBoxController::class, 'validateItem']);
});
