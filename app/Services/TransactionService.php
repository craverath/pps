<?php

namespace App\Services;

use App\DTOs\TransferDTO;
use App\Interfaces\{ITransactionRepository, IUserRepository, IWalletRepository};
use App\Exceptions\TransactionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransactionService
{
    public function __construct(
        private readonly ITransactionRepository $transactionRepository,
        private readonly IUserRepository $userRepository,
        private readonly IWalletRepository $walletRepository
    ) {}

    public function transfer(TransferDTO $transferDTO): array
    {
        return DB::transaction(function () use ($transferDTO) {
            try {
                // Busca e valida usuários
                $payer = $this->userRepository->findById($transferDTO->payer);
                $payee = $this->userRepository->findById($transferDTO->payee);

                if (!$payer || !$payee) {
                    throw new TransactionException('Usuário não encontrado');
                }

                if ($payer->isLojista()) {
                    throw new TransactionException('Lojistas não podem realizar transferências');
                }

                // Bloqueia e verifica saldo da carteira do pagador
                $payerWallet = $this->walletRepository->lockForUpdate($payer->wallet->id);
                if ($payerWallet->saldo < $transferDTO->value) {
                    throw new TransactionException('Saldo insuficiente');
                }

                // Autoriza a transação
                if (!$this->authorizeTransaction()) {
                    throw new TransactionException('Transação não autorizada');
                }

                // Cria a transação
                $transaction = $this->transactionRepository->create([
                    'valor' => $transferDTO->value,
                    'payer_id' => $transferDTO->payer,
                    'payee_id' => $transferDTO->payee,
                    'status' => 'pendente'
                ]);

                // Atualiza os saldos
                $this->walletRepository->updateBalance($payerWallet, $payerWallet->saldo - $transferDTO->value);
                $this->walletRepository->updateBalance($payee->wallet, $payee->wallet->saldo + $transferDTO->value);

                // Atualiza status da transação
                $this->transactionRepository->updateStatus($transaction, 'autorizada');

                // Notifica o recebedor (fora da transação pois não afeta a integridade dos dados)
                $this->notifyUser($payee);

                return [
                    'message' => 'Transferência realizada com sucesso',
                    'transaction_id' => $transaction->id
                ];
            } catch (\Exception $e) {
                // O rollback é automático quando uma exceção é lançada dentro do DB::transaction
                throw new TransactionException($e->getMessage());
            }
        });
    }

    private function authorizeTransaction(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        try {
            $response = Http::get('https://util.devi.tools/api/v2/authorize');
            
            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();
            return isset($data['message']) && $data['message'] === 'Autorizado';
        } catch (\Exception $e) {
            \Log::error('Erro ao autorizar transação: ' . $e->getMessage());
            return false;
        }
    }

    private function notifyUser($user): void
    {
        try {
            Http::post('https://util.devi.tools/api/v1/notify', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            // Log error but don't stop the process
            \Log::error('Falha ao notificar usuário: ' . $e->getMessage());
        }
    }
} 