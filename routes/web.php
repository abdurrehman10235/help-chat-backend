<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/{any}', function () {
    $file = public_path('index.html');

    if (!File::exists($file)) {
        abort(404);
    }

    return Response::make(File::get($file), 200, [
        'Content-Type' => 'text/html',
    ]);
})->where('any', '.*');