<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'valor',
        'payer_id',
        'payee_id',
        'status',
    ];

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function payee()
    {
        return $this->belongsTo(User::class, 'payee_id');
    }

    public function authorize()
    {
        $this->status = 'autorizada';
        $this->save();
    }

    public function refuse()
    {
        $this->status = 'recusada';
        $this->save();
    }

    public function reverse()
    {
        $this->status = 'estornada';
        $this->save();
    }
} 