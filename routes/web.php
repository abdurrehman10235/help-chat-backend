<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\WhatsAppBotController;

// WhatsApp Bot QR Code Interface
Route::get('/whatsapp', [WhatsAppBotController::class, 'showQRPage']);

Route::get('/app/{any}', function () {
    return File::get(public_path('app/index.html'));
})->where('any', '.*');

Route::get('/admin/{any}', function () {
    return file_get_contents(public_path('admin/index.html'));
})->where('any', '.*');