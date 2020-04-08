<?php

namespace Laravel\Mercadopago\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
// use Laravel\Mercadopago\Providers\ModuleServiceProvider;

class MPServiceProvider extends ServiceProvider
{
    public function boot(Router $router)
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // $this->app->register(ModuleServiceProvider::class);
        include __DIR__ . '/../Http/routes.php';
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
    }
}