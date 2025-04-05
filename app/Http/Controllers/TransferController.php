<?php

namespace App\Http\Controllers;

use App\DTOs\TransferDTO;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransferController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService
    ) {
    }

    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|numeric|min:0.01',
            'payer' => 'required|integer|exists:users,id',
            'payee' => 'required|integer|exists:users,id|different:payer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transferDTO = TransferDTO::fromRequest($request->all());
            $result = $this->transactionService->transfer($transferDTO);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
