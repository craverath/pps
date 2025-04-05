<?php

namespace Tests\Unit\Services;

use App\DTOs\TransferDTO;
use App\Enums\UserType;
use App\Exceptions\TransactionException;
use App\Interfaces\{ITransactionRepository, IUserRepository, IWalletRepository, INotificationLogRepository};
use App\Models\{User, Wallet, Transaction};
use App\Services\TransactionService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Mockery;

class TransactionServiceTest extends TestCase
{
    private $transactionRepository;
    private $userRepository;
    private $walletRepository;
    private $notificationLogRepository;
    private $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionRepository = Mockery::mock(ITransactionRepository::class);
        $this->userRepository = Mockery::mock(IUserRepository::class);
        $this->walletRepository = Mockery::mock(IWalletRepository::class);
        $this->notificationLogRepository = Mockery::mock(INotificationLogRepository::class);

        $this->transactionService = new TransactionService(
            $this->transactionRepository,
            $this->userRepository,
            $this->walletRepository,
            $this->notificationLogRepository
        );

        // Configura o ambiente como não sendo de teste para os cenários que precisam
        app()['env'] = 'production';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_transfer_successfully()
    {
        // Configura o ambiente como teste para autorizar automaticamente
        app()['env'] = 'testing';

        // Arrange
        Http::fake([
            'util.devi.tools/api/v1/notify' => Http::response(['message' => 'Notificado'], 200),
        ]);

        $payerWallet = Mockery::mock(Wallet::class);
        $payerWallet->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $payerWallet->shouldReceive('getAttribute')->with('saldo')->andReturn(100.00);

        $payeeWallet = Mockery::mock(Wallet::class);
        $payeeWallet->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $payeeWallet->shouldReceive('getAttribute')->with('saldo')->andReturn(50.00);

        $payer = new User([
            'id' => 1,
            'tipo_usuario' => 'comum',
            'nome_completo' => 'Pagador Teste',
            'email' => 'pagador@teste.com',
            'cpf_cnpj' => '12345678900'
        ]);
        $payer->setRelation('wallet', $payerWallet);

        $payee = new User([
            'id' => 2,
            'tipo_usuario' => 'comum',
            'nome_completo' => 'Recebedor Teste',
            'email' => 'recebedor@teste.com',
            'cpf_cnpj' => '98765432100'
        ]);
        $payee->setRelation('wallet', $payeeWallet);

        $transaction = Mockery::mock(Transaction::class);
        $transaction->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $transferDTO = new TransferDTO(50.00, 1, 2);

        // Expectations
        $this->userRepository->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($payer);

        $this->userRepository->shouldReceive('findById')
            ->with(2)
            ->once()
            ->andReturn($payee);

        $this->walletRepository->shouldReceive('lockForUpdate')
            ->with(1)
            ->once()
            ->andReturn($payerWallet);

        $this->transactionRepository->shouldReceive('create')
            ->once()
            ->andReturn($transaction);

        $this->transactionRepository->shouldReceive('updateStatus')
            ->with($transaction, 'autorizada')
            ->once();

        $this->walletRepository->shouldReceive('updateBalance')
            ->with($payerWallet, 50.00)
            ->once();

        $this->walletRepository->shouldReceive('updateBalance')
            ->with($payeeWallet, 100.00)
            ->once();

        $this->notificationLogRepository->shouldReceive('create')
            ->once();

        // Act
        $result = $this->transactionService->transfer($transferDTO);

        // Assert
        $this->assertEquals('Transferência realizada com sucesso', $result['message']);
        $this->assertEquals(1, $result['transaction_id']);
    }

    public function test_throws_exception_when_user_not_found()
    {
        // Arrange
        $transferDTO = new TransferDTO(50.00, 1, 2);

        $this->userRepository->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn(null);

        $this->userRepository->shouldReceive('findById')
            ->with(2)
            ->never();

        // Assert
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Usuário não encontrado');

        // Act
        try {
            $this->transactionService->transfer($transferDTO);
        } catch (TransactionException $e) {
            $this->assertEquals('Usuário não encontrado', $e->getMessage());
            throw $e;
        }
    }

    public function test_throws_exception_when_payer_is_lojista()
    {
        // Arrange
        $payer = new User([
            'id' => 1,
            'tipo_usuario' => 'lojista',
            'nome_completo' => 'Lojista Teste',
            'email' => 'lojista@teste.com',
            'cpf_cnpj' => '12345678900'
        ]);

        $transferDTO = new TransferDTO(50.00, 1, 2);

        $this->userRepository->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($payer);

        $this->userRepository->shouldReceive('findById')
            ->with(2)
            ->never();

        // Assert
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Lojistas não podem realizar transferências');

        // Act
        $this->transactionService->transfer($transferDTO);
    }

    public function test_throws_exception_when_insufficient_balance()
    {
        // Arrange
        $payerWallet = Mockery::mock(Wallet::class);
        $payerWallet->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $payerWallet->shouldReceive('getAttribute')->with('saldo')->andReturn(30.00);

        $payeeWallet = Mockery::mock(Wallet::class);
        $payeeWallet->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $payeeWallet->shouldReceive('getAttribute')->with('saldo')->andReturn(50.00);

        $payer = new User([
            'id' => 1,
            'tipo_usuario' => 'comum',
            'nome_completo' => 'Pagador Teste',
            'email' => 'pagador@teste.com',
            'cpf_cnpj' => '12345678900'
        ]);
        $payer->setRelation('wallet', $payerWallet);

        $payee = new User([
            'id' => 2,
            'tipo_usuario' => 'comum',
            'nome_completo' => 'Recebedor Teste',
            'email' => 'recebedor@teste.com',
            'cpf_cnpj' => '98765432100'
        ]);
        $payee->setRelation('wallet', $payeeWallet);

        $transferDTO = new TransferDTO(50.00, 1, 2);

        $this->userRepository->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($payer);

        $this->userRepository->shouldReceive('findById')
            ->with(2)
            ->once()
            ->andReturn($payee);

        $this->walletRepository->shouldReceive('lockForUpdate')
            ->with(1)
            ->once()
            ->andReturn($payerWallet);

        // Assert
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        // Act
        $this->transactionService->transfer($transferDTO);
    }

    public function test_throws_exception_when_transaction_not_authorized()
    {
        // Arrange
        $payerWallet = Mockery::mock(Wallet::class);
        $payerWallet->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $payerWallet->shouldReceive('getAttribute')->with('saldo')->andReturn(100.00);

        $payeeWallet = Mockery::mock(Wallet::class);
        $payeeWallet->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $payeeWallet->shouldReceive('getAttribute')->with('saldo')->andReturn(50.00);

        $payer = new User([
            'id' => 1,
            'tipo_usuario' => 'comum',
            'nome_completo' => 'Pagador Teste',
            'email' => 'pagador@teste.com',
            'cpf_cnpj' => '12345678900'
        ]);
        $payer->setRelation('wallet', $payerWallet);

        $payee = new User([
            'id' => 2,
            'tipo_usuario' => 'comum',
            'nome_completo' => 'Recebedor Teste',
            'email' => 'recebedor@teste.com',
            'cpf_cnpj' => '98765432100'
        ]);
        $payee->setRelation('wallet', $payeeWallet);

        $transaction = Mockery::mock(Transaction::class);
        $transaction->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $transferDTO = new TransferDTO(50.00, 1, 2);

        // Mock para simular falha na autorização
        Http::fake([
            'util.devi.tools/api/v2/authorize' => Http::response(['message' => 'error'], 500),
        ]);

        // Expectations
        $this->userRepository->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($payer);

        $this->userRepository->shouldReceive('findById')
            ->with(2)
            ->once()
            ->andReturn($payee);

        $this->walletRepository->shouldReceive('lockForUpdate')
            ->with(1)
            ->once()
            ->andReturn($payerWallet);

        $this->transactionRepository->shouldReceive('create')
            ->once()
            ->andReturn($transaction);

        $this->transactionRepository->shouldReceive('updateStatus')
            ->with($transaction, 'rejeitada')
            ->once();

        // Assert
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Transação não autorizada');

        // Act
        $this->transactionService->transfer($transferDTO);
    }
} 