<?php

namespace App\Repositories;

use App\Interfaces\INotificationLogRepository;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class NotificationLogRepository implements INotificationLogRepository
{
    public function create(array $data): mixed
    {
        try {
            // Garante que os campos JSON sejam tratados corretamente
            if (isset($data['request_payload'])) {
                if (is_string($data['request_payload'])) {
                    $decoded = json_decode($data['request_payload'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['request_payload'] = $decoded;
                    } else {
                        // Se não for um JSON válido, armazena como string
                        $data['request_payload'] = $data['request_payload'];
                    }
                }
            } else {
                $data['request_payload'] = [];
            }

            if (isset($data['response_payload'])) {
                if (is_string($data['response_payload'])) {
                    $decoded = json_decode($data['response_payload'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data['response_payload'] = $decoded;
                    } else {
                        // Se não for um JSON válido, armazena como string
                        $data['response_payload'] = $data['response_payload'];
                    }
                }
            } else {
                $data['response_payload'] = null;
            }

            return NotificationLog::create($data);
        } catch (\Exception $e) {
            Log::error('Erro ao criar log de notificação', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
