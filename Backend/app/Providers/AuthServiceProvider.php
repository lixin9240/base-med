<?php

namespace App\Providers;

use App\Auth\AdminUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // 管理员认证提供者：认证时校验账号状态，被禁用账号的已签发 token 同步失效
        Auth::provider('eloquent_admin', function ($app, array $config) {
            return new AdminUserProvider($app['hash'], $config['model']);
        });
    }
}
