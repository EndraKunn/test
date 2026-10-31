
<?php

use App\Models\Canteen;
use Illuminate\Support\Facades\Route;

Route::prefix('kantin/{canteen:slug}')
    ->name('customer.')
    ->group(function () {
        Route::get('/', function (Canteen $canteen) {
            return view('customer.home', [
                'canteen' => $canteen->slug,
            ]);
        })->name('home');
    });
