<?php

namespace Database\Factories;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'saldo' => $this->faker->randomFloat(2, 0, 1000),
        ];
    }
} 