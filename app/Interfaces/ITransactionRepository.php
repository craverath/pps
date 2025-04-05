<?php

namespace App\Interfaces;

use App\Models\Transaction;

interface ITransactionRepository
{
    public function findById(int $id): ?Transaction;
    public function create(array $data): Transaction;
    public function updateStatus(Transaction $transaction, string $status): bool;
    public function findByPayerId(int $payerId): array;
    public function findByPayeeId(int $payeeId): array;
} 