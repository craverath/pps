<?php

namespace Tests\Unit\Repositories;

use App\Models\{User, Wallet};
use App\Repositories\WalletRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private $walletRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletRepository = new WalletRepository();
    }

    public function testFindByUserIdSuccessfully()
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'saldo' => 100.00
        ]);

        // Act
        $result = $this->walletRepository->findByUserId($user->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals(100.00, $result->saldo);
    }

    public function testFindByUserIdReturnsNullWhenNotFound()
    {
        // Act
        $result = $this->walletRepository->findByUserId(999);

        // Assert
        $this->assertNull($result);
    }

    public function testCreateWalletSuccessfully()
    {
        // Arrange
        $user = User::factory()->create();
        $data = [
            'user_id' => $user->id,
            'saldo' => 0.00
        ];

        // Act
        $result = $this->walletRepository->create($data);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals(0.00, $result->saldo);
        $this->assertDatabaseHas('wallets', $data);
    }

    public function testUpdateBalanceSuccessfully()
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'saldo' => 100.00
        ]);

        // Act
        $result = $this->walletRepository->updateBalance($wallet, 200.00);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'saldo' => 200.00
        ]);
    }

    public function testLockForUpdateSuccessfully()
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'saldo' => 100.00
        ]);

        // Act
        $result = $this->walletRepository->lockForUpdate($wallet->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($wallet->id, $result->id);
        $this->assertEquals(100.00, $result->saldo);
    }

    public function testLockForUpdateReturnsNullWhenNotFound()
    {
        // Act
        $result = $this->walletRepository->lockForUpdate(999);

        // Assert
        $this->assertNull($result);
    }
}
