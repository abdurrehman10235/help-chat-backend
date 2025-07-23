<?php

use Illuminate\Support\Facades\Route;

// All routes moved to API to bypass web middleware issues
// Use these URLs instead:
// /api/web-test (instead of /test)
// /api/web-whatsapp (instead of /whatsapp)

Route::get('/', function () {
    return '<!DOCTYPE html><html><head><title>Laravel App</title></head><body><h1>Laravel Application is Running</h1><p>WhatsApp Bot: <a href="/api/web-whatsapp">/api/web-whatsapp</a></p><p>Test: <a href="/api/web-test">/api/web-test</a></p></body></html>';
});