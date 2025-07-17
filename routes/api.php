<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

Route::get('/image/{folder}/{filename}', function ($folder, $filename) {
    $filename = urldecode($filename);
    $path = "public/$folder/$filename";

    if (!Storage::exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    $file = Storage::get($path);
    $mime = Storage::mimeType($path);

    return Response::make($file, 200)
        ->header('Content-Type', $mime)
        ->header('Access-Control-Allow-Origin', '*');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/user/{id}', [UserController::class, 'show']);
Route::post('/verify-id', [UserController::class, 'verifyId']);
Route::post('/users', [UserController::class, 'store']);

// Existing Service routes
Route::get('/service-from-text', [ServiceController::class, 'searchServiceByText']);
Route::get('/service/{slug}', [ServiceController::class, 'show']);
Route::get('/service-slug', [ServiceController::class, 'getSlugByName']);
Route::get('/services', [ServiceController::class, 'index']);
Route::post('/service', [ServiceController::class, 'store']);
Route::post('/service/update/{slug}', [ServiceController::class, 'update']);
Route::delete('/service/{slug}', [ServiceController::class, 'destroy']);

// **NEW**: Endpoint to get service categories (with language support)
Route::get('/service-categories', [ServiceController::class, 'getCategories']);