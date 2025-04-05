<?php

namespace App\Interfaces;

interface INotificationLogRepository
{
    public function create(array $data): mixed;
}
