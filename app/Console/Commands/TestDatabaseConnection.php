<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestDatabaseConnection extends Command
{
    protected $signature = 'db:test';
    protected $description = 'Testa a conexão com o banco de dados';

    public function handle()
    {
        try {
            DB::connection()->getPdo();
            $this->info('✅ Conexão com o banco de dados estabelecida com sucesso!');
            $this->info('Banco de dados: ' . DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            $this->error('❌ Erro ao conectar com o banco de dados: ' . $e->getMessage());
        }
    }
}
