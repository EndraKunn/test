<?php

use App\Models\Menu;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::prefix('tenant/{tenant:slug}')
    ->name('tenant.')
    ->middleware(['auth', 'verified', 'tenant.context'])
    ->scopeBindings()
    ->group(function () {
        Route::get('/dashboard', function (Tenant $tenant) {
            return "Dashboard tenant: {$tenant->display_name}";
        })->name('dashboard');

        Route::get('/menus/{menu}', function (
            Tenant $tenant,
            Menu $menu,
        ) {
            return "Menu: {$menu->name}";
        })->name('menus.show');

        Route::get('/menu-manager', function (Tenant $tenant) {
            return view('catalog::tenant.menu-manager', [
                'tenant' => $tenant,
            ]);
        })->name('menu-manager');

        Route::get('/modifiers', function (Tenant $tenant) {
            return view('catalog::tenant.modifier-manager', [
                'tenant' => $tenant,
            ]);
        })->name('modifier-manager');
    });
