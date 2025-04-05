<?php

namespace App\Interfaces;

use App\Models\User;

interface IUserRepository
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByCpfCnpj(string $cpfCnpj): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): bool;
    public function delete(int $id): bool;
}
