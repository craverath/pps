<?php

namespace App\Services;

use App\DTOs\CreateUserDTO;
use App\Interfaces\{IUserRepository, IWalletRepository};
use App\Exceptions\UserException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly IUserRepository $userRepository,
        private readonly IWalletRepository $walletRepository
    ) {}

    public function createUser(CreateUserDTO $userDTO): array
    {
        return DB::transaction(function () use ($userDTO) {
            // Verifica se já existe usuário com o mesmo CPF/CNPJ ou email
            if ($this->userRepository->findByCpfCnpj($userDTO->cpfCnpj)) {
                throw new UserException('CPF/CNPJ já cadastrado');
            }

            if ($this->userRepository->findByEmail($userDTO->email)) {
                throw new UserException('Email já cadastrado');
            }

            // Cria o usuário
            $user = $this->userRepository->create([
                'nome_completo' => $userDTO->nomeCompleto,
                'cpf_cnpj' => $userDTO->cpfCnpj,
                'email' => $userDTO->email,
                'password' => Hash::make($userDTO->password),
                'tipo_usuario' => $userDTO->tipoUsuario->value
            ]);

            // Cria a carteira do usuário
            $wallet = $this->walletRepository->create([
                'user_id' => $user->id,
                'saldo' => 0.00
            ]);

            return [
                'id' => $user->id,
                'nome_completo' => $user->nome_completo,
                'cpf_cnpj' => $user->cpf_cnpj,
                'email' => $user->email,
                'tipo_usuario' => $user->tipo_usuario,
                'saldo_inicial' => $wallet->saldo,
                'created_at' => $user->created_at
            ];
        });
    }
} 