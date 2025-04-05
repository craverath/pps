<?php

namespace App\DTOs;

use App\Enums\UserType;

class CreateUserDTO
{
    public function __construct(
        public readonly string $nomeCompleto,
        public readonly string $cpfCnpj,
        public readonly string $email,
        public readonly string $password,
        public readonly UserType $tipoUsuario = UserType::COMUM
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            nomeCompleto: $data['nome_completo'],
            cpfCnpj: $data['cpf_cnpj'],
            email: $data['email'],
            password: $data['password'],
            tipoUsuario: UserType::from($data['tipo_usuario'] ?? 'comum')
        );
    }
}
