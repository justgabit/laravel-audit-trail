<?php

namespace Mueva\AuditTrail;

use Illuminate\Support\ServiceProvider;

class AuditTrailServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configFile = realpath(__DIR__ . '/../config/audit-trail.php');

        $this->publishes([$configFile => config_path('audit-trail.php')]);
        $this->mergeConfigFrom($configFile, 'audit-trail');
        $this->loadMigrationsFrom(realpath(__DIR__ . '/../migrations/'));
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AuditTrail::class, function ($app) {
            $settings = new AuditTrail;
            return $settings;
        });
    }
}
