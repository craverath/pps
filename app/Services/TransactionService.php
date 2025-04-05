<?php

namespace App\Services;

use App\DTOs\TransferDTO;
use App\Interfaces\{ITransactionRepository, IUserRepository, IWalletRepository, INotificationLogRepository};
use App\Exceptions\TransactionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransactionService
{
    public function __construct(
        private readonly ITransactionRepository $transactionRepository,
        private readonly IUserRepository $userRepository,
        private readonly IWalletRepository $walletRepository,
        private readonly INotificationLogRepository $notificationLogRepository
    ) {}

    public function transfer(TransferDTO $transferDTO): array
    {
        return DB::transaction(function () use ($transferDTO) {
            try {
                // Busca e valida o pagador
                $payer = $this->userRepository->findById($transferDTO->payer);
                if (!$payer) {
                    throw new TransactionException('Usuário não encontrado');
                }

                if ($payer->isLojista()) {
                    throw new TransactionException('Lojistas não podem realizar transferências');
                }

                // Busca e valida o recebedor
                $payee = $this->userRepository->findById($transferDTO->payee);
                if (!$payee) {
                    throw new TransactionException('Usuário não encontrado');
                }

                // Bloqueia e verifica saldo da carteira do pagador
                $payerWallet = $this->walletRepository->lockForUpdate($payer->wallet->id);
                if ($payerWallet->saldo < $transferDTO->value) {
                    throw new TransactionException('Saldo insuficiente');
                }

                // Cria a transação com status pendente
                $transaction = $this->transactionRepository->create([
                    'valor' => $transferDTO->value,
                    'payer_id' => $transferDTO->payer,
                    'payee_id' => $transferDTO->payee,
                    'status' => 'pendente'
                ]);

                // Autoriza a transação
                if (!$this->authorizeTransaction()) {
                    $this->transactionRepository->updateStatus($transaction, 'rejeitada');
                    throw new TransactionException('Transação não autorizada');
                }

                // Atualiza status para autorizada
                $this->transactionRepository->updateStatus($transaction, 'autorizada');

                // Atualiza os saldos
                $this->walletRepository->updateBalance($payerWallet, $payerWallet->saldo - $transferDTO->value);
                $this->walletRepository->updateBalance($payee->wallet, $payee->wallet->saldo + $transferDTO->value);

                // Notifica o recebedor (fora da transação pois não afeta a integridade dos dados)
                $this->notifyUser($payee, $transaction);

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
        if (app()->environment('testing') || app()->environment('local')) {
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

    private function notifyUser($user, $transaction): void
    {
        $requestPayload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'transaction_id' => $transaction->id
        ];

        try {
            $response = Http::post('https://util.devi.tools/api/v1/notify', $requestPayload);
            
            $logData = [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'status' => $response->successful() ? 'success' : 'error',
                'error_message' => !$response->successful() ? $response->body() : null,
                'request_payload' => json_encode($requestPayload),
                'response_payload' => $response->json() ? json_encode($response->json()) : null
            ];

            // Registra o log da notificação
            $this->notificationLogRepository->create($logData);

            if (!$response->successful()) {
                \Log::warning('Notificação falhou', [
                    'user_id' => $user->id,
                    'transaction_id' => $transaction->id,
                    'response' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            $logData = [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'request_payload' => json_encode($requestPayload),
                'response_payload' => null
            ];

            // Registra o log da falha
            $this->notificationLogRepository->create($logData);

            \Log::error('Falha ao notificar usuário', [
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
} 