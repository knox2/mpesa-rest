<?php

namespace Knox\MPESA;

use Illuminate\Support\ServiceProvider;

class MpesaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/mpesa.php' => config_path('mpesa.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__.'/config/mpesa-default.php', 'mpesa'
        );
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MPESA::class, function () {
            return new MPESA();
        });
        $this->app->alias(MPESA::class, 'MPESA');
    }
}
