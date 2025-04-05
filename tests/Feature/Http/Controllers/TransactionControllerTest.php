<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\UserType;
use App\Models\{User, Wallet};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    private $commonUser;
    private $merchantUser;
    private $commonUser2;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuários comuns e lojista para os testes
        $this->commonUser = User::factory()->create([
            'tipo_usuario' => UserType::COMUM->value,
            'cpf_cnpj' => '12345678901',
            'email' => 'comum1@test.com'
        ]);

        $this->merchantUser = User::factory()->create([
            'tipo_usuario' => UserType::LOJISTA->value,
            'cpf_cnpj' => '98765432101',
            'email' => 'lojista@test.com'
        ]);

        $this->commonUser2 = User::factory()->create([
            'tipo_usuario' => UserType::COMUM->value,
            'cpf_cnpj' => '12345678902',
            'email' => 'comum2@test.com'
        ]);

        // Criar carteiras com saldos iniciais
        Wallet::factory()->create([
            'user_id' => $this->commonUser->id,
            'saldo' => 1000.00
        ]);

        Wallet::factory()->create([
            'user_id' => $this->merchantUser->id,
            'saldo' => 0.00
        ]);

        Wallet::factory()->create([
            'user_id' => $this->commonUser2->id,
            'saldo' => 500.00
        ]);
    }

    public function test_transfer_between_common_and_merchant_successfully()
    {
        // Arrange
        $transferData = [
            'value' => 100.00,
            'payer' => $this->commonUser->id,
            'payee' => $this->merchantUser->id
        ];

        // Mock do serviço de autorização
        Http::fake([
            'util.devi.tools/api/v2/authorize' => Http::response(['message' => 'Autorizado'], 200),
            'util.devi.tools/api/v1/notify' => Http::response(['message' => 'Notificado'], 200),
        ]);

        // Act
        $response = $this->postJson('/api/transfer', $transferData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transferência realizada com sucesso'
            ]);

        // Verificar saldos atualizados
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->commonUser->id,
            'saldo' => 900.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->merchantUser->id,
            'saldo' => 100.00
        ]);

        // Verificar log de notificação
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->merchantUser->id,
            'status' => 'success'
        ]);
    }

    public function test_transfer_between_common_users_successfully()
    {
        // Arrange
        $transferData = [
            'value' => 200.00,
            'payer' => $this->commonUser->id,
            'payee' => $this->commonUser2->id
        ];

        // Mock do serviço de autorização
        Http::fake([
            'util.devi.tools/api/v2/authorize' => Http::response(['message' => 'Autorizado'], 200),
            'util.devi.tools/api/v1/notify' => Http::response(['message' => 'Notificado'], 200),
        ]);

        // Act
        $response = $this->postJson('/api/transfer', $transferData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transferência realizada com sucesso'
            ]);

        // Verificar saldos atualizados
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->commonUser->id,
            'saldo' => 800.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->commonUser2->id,
            'saldo' => 700.00
        ]);
    }

    public function test_transfer_denied_due_to_insufficient_balance()
    {
        // Arrange
        $transferData = [
            'value' => 2000.00, // Valor maior que o saldo disponível
            'payer' => $this->commonUser->id,
            'payee' => $this->merchantUser->id
        ];

        // Act
        $response = $this->postJson('/api/transfer', $transferData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
                'message' => 'Saldo insuficiente'
            ]);

        // Verificar que os saldos não foram alterados
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->commonUser->id,
            'saldo' => 1000.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->merchantUser->id,
            'saldo' => 0.00
        ]);
    }

    public function test_transfer_denied_due_to_authorization_failure()
    {
        // Arrange
        $transferData = [
            'value' => 100.00,
            'payer' => $this->commonUser->id,
            'payee' => $this->merchantUser->id
        ];

        // Mock do serviço de autorização para falhar
        Http::fake([
            'util.devi.tools/api/v2/authorize' => Http::response(['message' => 'Não autorizado'], 401),
        ]);

        // Act
        $response = $this->postJson('/api/transfer', $transferData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
                'message' => 'Transação não autorizada'
            ]);

        // Verificar que os saldos não foram alterados
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->commonUser->id,
            'saldo' => 1000.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->merchantUser->id,
            'saldo' => 0.00
        ]);
    }

    public function test_notification_failure_and_retry()
    {
        // Arrange
        $transferData = [
            'value' => 100.00,
            'payer' => $this->commonUser->id,
            'payee' => $this->merchantUser->id
        ];

        // Mock do serviço de autorização e notificação
        Http::fake([
            'util.devi.tools/api/v2/authorize' => Http::response(['message' => 'Autorizado'], 200),
            'util.devi.tools/api/v1/notify' => Http::sequence()
                ->push(['message' => 'Erro'], 500) // Primeira tentativa falha
                ->push(['message' => 'Notificado'], 200), // Segunda tentativa sucesso
        ]);

        // Act
        $response = $this->postJson('/api/transfer', $transferData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Transferência realizada com sucesso'
            ]);

        // Verificar saldos atualizados
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->commonUser->id,
            'saldo' => 900.00
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->merchantUser->id,
            'saldo' => 100.00
        ]);

        // Verificar logs de notificação
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->merchantUser->id,
            'status' => 'error'
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $this->merchantUser->id,
            'status' => 'success'
        ]);
    }

    public function test_concurrent_transfers()
    {
        // Arrange
        $transferData = [
            'value' => 100.00,
            'payer' => $this->commonUser->id,
            'payee' => $this->merchantUser->id
        ];

        // Mock do serviço de autorização e notificação
        Http::fake([
            'util.devi.tools/api/v2/authorize' => Http::response(['message' => 'Autorizado'], 200),
            'util.devi.tools/api/v1/notify' => Http::response(['message' => 'Notificado'], 200),
        ]);

        // Simular requisições concorrentes
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->postJson('/api/transfer', $transferData);
        }

        // Assert
        foreach ($responses as $response) {
            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Transferência realizada com sucesso'
                ]);
        }

        // Verificar saldo final do pagador
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->commonUser->id,
            'saldo' => 500.00 // 1000 - (5 * 100)
        ]);

        // Verificar saldo final do recebedor
        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->merchantUser->id,
            'saldo' => 500.00 // 0 + (5 * 100)
        ]);
    }
} 