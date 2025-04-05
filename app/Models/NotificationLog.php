<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'status',
        'error_message',
        'request_payload',
        'response_payload'
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 