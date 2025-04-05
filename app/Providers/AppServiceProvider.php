<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\{IUserRepository, IWalletRepository, ITransactionRepository};
use App\Repositories\{UserRepository, WalletRepository, TransactionRepository};

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
