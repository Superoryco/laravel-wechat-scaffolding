<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Auth;
use App\Extensions\SessionToUserProvider;
use App\Extensions\WX3rdSessionGuard;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();

        Auth::extend('3rd_session', function ($app, $name, array $config) {
            // automatically build the DI, put it as reference
            $userProvider = app(SessionToUserProvider::class);
            $request = app('request');
            return new WX3rdSessionGuard($userProvider, $request, $config);
        });
    }
}
