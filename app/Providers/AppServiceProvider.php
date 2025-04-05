<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\{IUserRepository, IWalletRepository, ITransactionRepository};
use App\Repositories\{UserRepository, WalletRepository, TransactionRepository};
use App\Services\UserService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IUserRepository::class, UserRepository::class);
        $this->app->bind(IWalletRepository::class, WalletRepository::class);
        $this->app->bind(ITransactionRepository::class, TransactionRepository::class);
        
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService(
                $app->make(IUserRepository::class),
                $app->make(IWalletRepository::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
