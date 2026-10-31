<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Home';
})->name('home');

Route::get('/dashboard', function () {
    return 'Dashboard';
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::livewire('/settings/profile', 'pages::settings.profile')
        ->name('profile.edit');
});

Route::middleware(['auth', 'password.confirm'])->group(function () {
    Route::livewire('/settings/security', 'pages::settings.security')
        ->name('security.edit');
});

require __DIR__.'/customer.php';
require __DIR__.'/tenant.php';
require __DIR__.'/admin.php';
require app_path('Modules/Ordering/routes/web.php');
