<?php

use App\Application\Http\Controllers\V1\CartonBoxController;
use App\Application\Http\Controllers\V1\ValidationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/carton-boxes', [CartonBoxController::class, 'index']);
    Route::post('/carton-boxes/{id}/process', [CartonBoxController::class, 'process']);
    Route::get('/carton-boxes/po', [CartonBoxController::class, 'getPOs']);
    Route::get('/carton-boxes/sku', [CartonBoxController::class, 'getSKUs']);
});
