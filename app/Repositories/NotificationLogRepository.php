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
            if (isset($data['request_payload']) && is_string($data['request_payload'])) {
                $data['request_payload'] = json_decode($data['request_payload'], true);
            }

            if (isset($data['response_payload']) && is_string($data['response_payload'])) {
                $data['response_payload'] = json_decode($data['response_payload'], true);
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