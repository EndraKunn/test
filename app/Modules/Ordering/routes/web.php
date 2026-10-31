<?php

use App\Modules\Ordering\Http\Controllers\ResolveTableQrController;
use Illuminate\Support\Facades\Route;

Route::get('/q/{token}', ResolveTableQrController::class)
    ->middleware('throttle:qr-scan')
    ->name('customer.scan');
