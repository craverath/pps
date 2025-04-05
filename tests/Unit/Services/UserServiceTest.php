<?php

namespace Tests\Unit\Services;

use App\DTOs\CreateUserDTO;
use App\Enums\UserType;
use App\Exceptions\UserException;
use App\Interfaces\{IUserRepository, IWalletRepository};
use App\Models\{User, Wallet};
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private $userRepository;
    private $walletRepository;
    private $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(IUserRepository::class);
        $this->walletRepository = Mockery::mock(IWalletRepository::class);
        $this->userService = new UserService($this->userRepository, $this->walletRepository);
    }

    public function testCreateUserSuccessfully()
    {
        // Arrange
        $userDTO = new CreateUserDTO(
            nomeCompleto: 'João Silva',
            cpfCnpj: '12345678909',
            email: 'joao@test.com',
            password: 'password123',
            tipoUsuario: UserType::COMUM
        );

        $user = new User([
            'id' => 1,
            'nome_completo' => 'João Silva',
            'cpf_cnpj' => '12345678909',
            'email' => 'joao@test.com',
            'tipo_usuario' => 'comum',
            'created_at' => now()
        ]);
        $user->id = 1;

        $wallet = new Wallet([
            'id' => 1,
            'user_id' => 1,
            'saldo' => 0.00
        ]);

        $this->userRepository
            ->shouldReceive('findByCpfCnpj')
            ->with('12345678909')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('joao@test.com')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('create')
            ->andReturn($user);

        $this->walletRepository
            ->shouldReceive('create')
            ->andReturn($wallet);

        // Act
        $result = $this->userService->createUser($userDTO);

        // Assert
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('João Silva', $result['nome_completo']);
        $this->assertEquals('12345678909', $result['cpf_cnpj']);
        $this->assertEquals('joao@test.com', $result['email']);
        $this->assertEquals('comum', $result['tipo_usuario']);
        $this->assertEquals(0.00, $result['saldo_inicial']);
    }

    public function testThrowsExceptionWhenCpfCnpjAlreadyExists()
    {
        // Arrange
        $userDTO = new CreateUserDTO(
            nomeCompleto: 'João Silva',
            cpfCnpj: '12345678909',
            email: 'joao@test.com',
            password: 'password123',
            tipoUsuario: UserType::COMUM
        );

        $existingUser = new User();

        $this->userRepository
            ->shouldReceive('findByCpfCnpj')
            ->with('12345678909')
            ->andReturn($existingUser);

        // Assert
        $this->expectException(UserException::class);
        $this->expectExceptionMessage('CPF/CNPJ já cadastrado');

        // Act
        $this->userService->createUser($userDTO);
    }

    public function testThrowsExceptionWhenEmailAlreadyExists()
    {
        // Arrange
        $userDTO = new CreateUserDTO(
            nomeCompleto: 'João Silva',
            cpfCnpj: '12345678909',
            email: 'joao@test.com',
            password: 'password123',
            tipoUsuario: UserType::COMUM
        );

        $existingUser = new User();

        $this->userRepository
            ->shouldReceive('findByCpfCnpj')
            ->with('12345678909')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('joao@test.com')
            ->andReturn($existingUser);

        // Assert
        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Email já cadastrado');

        // Act
        $this->userService->createUser($userDTO);
    }
}
