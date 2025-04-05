<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'saldo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function addSaldo($valor)
    {
        $this->saldo += $valor;
        $this->save();
    }

    public function subtractSaldo($valor)
    {
        $this->saldo -= $valor;
        $this->save();
    }
}
