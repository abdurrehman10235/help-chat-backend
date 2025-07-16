<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/{any}', function () {
    return File::get(public_path('index.html'));
})->where('any', '.*');