<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'valor' => $this->faker->randomFloat(2, 10, 1000),
            'payer_id' => \App\Models\User::factory(),
            'payee_id' => \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['pendente', 'autorizada', 'rejeitada']),
        ];
    }
} 