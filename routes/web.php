<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/app/{any}', function () {
    return File::get(public_path('app/index.html'));
})->where('any', '.*');