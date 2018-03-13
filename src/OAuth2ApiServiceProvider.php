<?php

namespace Batmahir\OAuth2;

use Illuminate\Support\ServiceProvider;

class OAuth2ApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {


        $this->publishes([
            __DIR__.'/2018_03_13_143619_oauth2migration.php' => base_path().'/database/migrations/2018_03_13_143619_oauth2migration.php',
        ],'oauth2_migration');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
