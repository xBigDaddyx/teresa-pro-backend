<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Teresa' => app()->version()];
});
