<?php

namespace App\DTOs;

class TransferDTO
{
    public function __construct(
        public readonly float $value,
        public readonly int $payer,
        public readonly int $payee
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            value: (float) $data['value'],
            payer: (int) $data['payer'],
            payee: (int) $data['payee']
        );
    }
} 