<?php

namespace App\Repositories;

use App\Interfaces\IWalletRepository;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletRepository implements IWalletRepository
{
    public function findByUserId(int $userId): ?Wallet
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function create(array $data): Wallet
    {
        return Wallet::create($data);
    }

    public function updateBalance(Wallet $wallet, float $newBalance): bool
    {
        return $wallet->update(['saldo' => $newBalance]);
    }

    public function lockForUpdate(int $walletId): ?Wallet
    {
        return DB::transaction(function () use ($walletId) {
            return Wallet::where('id', $walletId)
                ->lockForUpdate()
                ->first();
        });
    }
}
