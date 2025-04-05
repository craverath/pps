<?php

namespace Tests\Unit\Repositories;

use App\Models\{Transaction, User, NotificationLog};
use App\Repositories\NotificationLogRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private $notificationLogRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationLogRepository = new NotificationLogRepository();
    }

    public function test_create_notification_log_successfully()
    {
        // Arrange
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();
        
        $data = [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'status' => 'success',
            'error_message' => null,
            'request_payload' => json_encode(['test' => 'data']),
            'response_payload' => json_encode(['message' => 'success'])
        ];

        // Act
        $result = $this->notificationLogRepository->create($data);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($transaction->id, $result->transaction_id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals('success', $result->status);
        $this->assertNull($result->error_message);
        $this->assertEquals(['test' => 'data'], $result->request_payload);
        $this->assertEquals(['message' => 'success'], $result->response_payload);
        $this->assertDatabaseHas('notification_logs', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'status' => 'success'
        ]);
    }

    public function test_create_notification_log_with_error()
    {
        // Arrange
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();
        
        $data = [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'status' => 'error',
            'error_message' => 'Falha na notificação',
            'request_payload' => json_encode(['test' => 'data']),
            'response_payload' => null
        ];

        // Act
        $result = $this->notificationLogRepository->create($data);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($transaction->id, $result->transaction_id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals('error', $result->status);
        $this->assertEquals('Falha na notificação', $result->error_message);
        $this->assertEquals(['test' => 'data'], $result->request_payload);
        $this->assertNull($result->response_payload);
        $this->assertDatabaseHas('notification_logs', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'status' => 'error',
            'error_message' => 'Falha na notificação'
        ]);
    }

    public function test_create_notification_log_with_invalid_json()
    {
        // Arrange
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create();
        
        $data = [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'status' => 'success',
            'error_message' => null,
            'request_payload' => 'invalid json',
            'response_payload' => 'invalid json'
        ];

        // Act
        $result = $this->notificationLogRepository->create($data);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($transaction->id, $result->transaction_id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals('success', $result->status);
        $this->assertNull($result->error_message);
        $this->assertEquals('invalid json', $result->request_payload);
        $this->assertEquals('invalid json', $result->response_payload);
        $this->assertDatabaseHas('notification_logs', [
            'transaction_id' => $transaction->id,
            'user_id' => $user->id,
            'status' => 'success'
        ]);
    }
} 