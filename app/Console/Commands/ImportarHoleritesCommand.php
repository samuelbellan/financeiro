<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SalaryPayslip;
use App\Models\User;
use Illuminate\Console\Command;

class ImportarHoleritesCommand extends Command
{
    protected $signature = 'salary:importar-holerites
                            {arquivo? : JSON com os holerites (padrão: database/dumps/holerites_tjms.json)}
                            {--user=1 : ID do usuário dono dos holerites}';

    protected $description = 'Grava os holerites reais de um usuário para a aba Real × Projetado do projetor salarial';

    public function handle(): int
    {
        $arquivo = $this->argument('arquivo') ?: database_path('dumps/holerites_tjms.json');
        $user = User::find((int) $this->option('user'));

        if (!$user) {
            $this->error('Usuário não encontrado: ' . $this->option('user'));
            return self::FAILURE;
        }
        if (!is_file($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");
            return self::FAILURE;
        }

        $holerites = json_decode((string) file_get_contents($arquivo), true);
        if (!is_array($holerites)) {
            $this->error('O arquivo não contém um JSON válido.');
            return self::FAILURE;
        }

        $gravados = 0;
        foreach ($holerites as $h) {
            if (!preg_match('/^\d{4}-\d{2}$/', (string) ($h['mes_ano'] ?? ''))) {
                $this->warn('Registro ignorado (mes_ano inválido): ' . json_encode($h['mes_ano'] ?? null));
                continue;
            }

            $dados = [
                'total_bruto' => $h['total_bruto'],
                'total_descontos' => $h['total_descontos'],
                'total_liquido' => $h['total_liquido'],
                'base_rpps' => $h['base_rpps'] ?? null,
                'base_irrf' => $h['base_irrf'] ?? null,
                'pagamentos' => $h['pagamentos'] ?? [],
                'descontos' => $h['descontos'] ?? [],
                'observacao' => $h['observacao'] ?? null,
            ];
            // Só mexe na marcação de outlier se o arquivo trouxer o campo: preserva o que foi marcado na tela
            if (array_key_exists('outlier', $h)) {
                $dados['outlier'] = (bool) $h['outlier'];
            }

            // Reimportar o mesmo mês substitui o registro, sem duplicar
            SalaryPayslip::updateOrCreate(['user_id' => $user->id, 'mes_ano' => $h['mes_ano']], $dados);
            $gravados++;
        }

        $this->info("{$gravados} holerite(s) gravado(s) para {$user->name}.");

        return self::SUCCESS;
    }
}
