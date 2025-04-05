<?php

namespace App\Interfaces;

use App\Models\Wallet;

interface IWalletRepository
{
    public function findByUserId(int $userId): ?Wallet;
    public function create(array $data): Wallet;
    public function updateBalance(Wallet $wallet, float $newBalance): bool;
    public function lockForUpdate(int $walletId): ?Wallet;
}
