<?php

namespace Batmahir\OAuth2;

use Illuminate\Support\ServiceProvider;
use League\OAuth2\Server\ResourceServer;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Facades\Auth;


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
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->register();
    }

    public function registerToken()
    {
        $config2  = config('auth.guards.api');
        $data = new \Batmahir\OAuth2\Token(
            $this->app->make(ResourceServer::class),
            Auth::createUserProvider($config2['provider']),
            $this->app->make(TokenRepository::class),
            $this->app->make(ClientRepository::class),
            $this->app->make('encrypter')
        );
    }
}
