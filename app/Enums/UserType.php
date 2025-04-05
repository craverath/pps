<?php

namespace App\Enums;

enum UserType: string
{
    case COMUM = 'comum';
    case LOJISTA = 'lojista';

    public function isLojista(): bool
    {
        return $this === self::LOJISTA;
    }
} 