<?php

namespace App\Http\Controllers;

use App\DTOs\CreateUserDTO;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome_completo' => 'required|string|max:255',
            'cpf_cnpj' => 'required|string|min:11|max:14',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
            'tipo_usuario' => 'required|string|in:comum,lojista'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $userDTO = CreateUserDTO::fromRequest($request->all());
            $user = $this->userService->createUser($userDTO);
            
            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 422);
        }
    }
} 