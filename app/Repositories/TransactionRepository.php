<?php

namespace App\Repositories;

use App\Interfaces\ITransactionRepository;
use App\Models\Transaction;

class TransactionRepository implements ITransactionRepository
{
    public function findById(int $id): ?Transaction
    {
        return Transaction::find($id);
    }

    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function updateStatus(Transaction $transaction, string $status): bool
    {
        return $transaction->update(['status' => $status]);
    }

    public function findByPayerId(int $payerId): array
    {
        return Transaction::where('payer_id', $payerId)->get()->all();
    }

    public function findByPayeeId(int $payeeId): array
    {
        return Transaction::where('payee_id', $payeeId)->get()->all();
    }
}
