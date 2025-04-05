<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Interfaces\{IUserRepository, IWalletRepository, ITransactionRepository, INotificationLogRepository};
use App\Repositories\{UserRepository, WalletRepository, TransactionRepository, NotificationLogRepository};
use App\Services\{UserService, TransactionService};

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
        $this->app->bind(INotificationLogRepository::class, NotificationLogRepository::class);

        $this->app->singleton(UserService::class, function ($app) {
            return new UserService(
                $app->make(IUserRepository::class),
                $app->make(IWalletRepository::class)
            );
        });

        $this->app->singleton(TransactionService::class, function ($app) {
            return new TransactionService(
                $app->make(ITransactionRepository::class),
                $app->make(IUserRepository::class),
                $app->make(IWalletRepository::class),
                $app->make(INotificationLogRepository::class)
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
