<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user()
    {
        // Arrange
        $userData = [
            'nome_completo' => 'João Silva',
            'cpf_cnpj' => '12345678909',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'tipo_usuario' => 'comum'
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);
        $responseData = $response->json();

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'nome_completo' => 'João Silva',
                'cpf_cnpj' => '12345678909',
                'email' => 'joao@test.com',
                'tipo_usuario' => 'comum',
                'saldo_inicial' => 0.00
            ]);

        $this->assertDatabaseHas('users', [
            'nome_completo' => 'João Silva',
            'cpf_cnpj' => '12345678909',
            'email' => 'joao@test.com',
            'tipo_usuario' => 'comum'
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $responseData['id'],
            'saldo' => 0.00
        ]);
    }

    public function test_cannot_create_user_with_duplicate_cpf_cnpj()
    {
        // Arrange
        User::factory()->create([
            'cpf_cnpj' => '12345678909'
        ]);

        $userData = [
            'nome_completo' => 'João Silva',
            'cpf_cnpj' => '12345678909',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'tipo_usuario' => 'comum'
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
                'message' => 'CPF/CNPJ já cadastrado'
            ]);
    }

    public function test_cannot_create_user_with_duplicate_email()
    {
        // Arrange
        User::factory()->create([
            'email' => 'joao@test.com'
        ]);

        $userData = [
            'nome_completo' => 'João Silva',
            'cpf_cnpj' => '12345678909',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'tipo_usuario' => 'comum'
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
                'message' => 'Email já cadastrado'
            ]);
    }

    public function test_validation_errors()
    {
        // Arrange
        $userData = [
            'nome_completo' => '',
            'cpf_cnpj' => '123',
            'email' => 'invalid-email',
            'password' => '123',
            'tipo_usuario' => 'invalid'
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'error',
                'message',
                'errors' => [
                    'nome_completo',
                    'cpf_cnpj',
                    'email',
                    'password',
                    'tipo_usuario'
                ]
            ]);
    }
} 