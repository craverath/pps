<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'transaction_id' => \App\Models\Transaction::factory(),
            'user_id' => \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['success', 'error']),
            'error_message' => null,
            'request_payload' => json_encode(['test' => 'data']),
            'response_payload' => json_encode(['message' => 'success']),
        ];
    }
} 