<?php

namespace App\Exceptions;

class TransactionException extends \Exception
{
    public function render($request)
    {
        return response()->json([
            'error' => true,
            'message' => $this->getMessage()
        ], 422);
    }
}
