<?php

namespace App\Modules\Catalog;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/resources/views',
            'catalog',
        );

        Livewire::addNamespace(
            'catalog',
            viewPath: app_path('Modules/Catalog/resources/views/livewire'),
        );
    }
}
