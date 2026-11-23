<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Concursos\MaterialImportador;
use App\Services\Concursos\MaterialQuestoesImportador;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Importa (ou atualiza) um material de resumo a partir de um JSON.
 *   php artisan concursos:importar-material storage/concursos/materiais/dtrib.json --user=fulano@x.com
 * Sem --user, usa o dono do sistema. O JSON fica em storage/concursos (fora do git: o repositório é público).
 */
class ImportarMaterialCommand extends Command
{
    protected $signature = 'concursos:importar-material {arquivo : JSON do material} {--user= : e-mail ou id do aluno (padrão: o dono)}
                            {--questoes= : JSON com as questões erradas dos cadernos (scripts/concursos/extrair_caderno_erros.py)}';

    protected $description = 'Importa um material de resumo (cadernos de erros) para o módulo de concursos.';

    public function handle(MaterialImportador $importador): int
    {
        if (!config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');
            return self::FAILURE;
        }

        $arquivo = $this->argument('arquivo');
        if (!is_file($arquivo)) {
            $this->error("Não encontrei: {$arquivo}");
            return self::FAILURE;
        }

        $quem = $this->option('user');
        $user = $quem === null
            ? User::where('is_owner', true)->orderBy('id')->first()
            : User::where(ctype_digit((string) $quem) ? 'id' : 'email', $quem)->first();
        if (!$user) {
            $this->error('Usuário não encontrado.');
            return self::FAILURE;
        }

        $dados = json_decode((string) file_get_contents($arquivo), true);
        if (!is_array($dados)) {
            $this->error('JSON inválido: ' . json_last_error_msg());
            return self::FAILURE;
        }

        try {
            $r = $importador->importar($dados, $user->id);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("OK \"{$r['material']->titulo}\" para {$user->name}: {$r['secoes']} seções, {$r['novos']} pontos novos, {$r['atualizados']} atualizados.");

        if ($arqQ = $this->option('questoes')) {
            $dadosQ = is_file($arqQ) ? json_decode((string) file_get_contents($arqQ), true) : null;
            if (!is_array($dadosQ)) {
                $this->error("Questões: não consegui ler {$arqQ}.");
                return self::FAILURE;
            }
            try {
                $q = app(MaterialQuestoesImportador::class)->importar($dadosQ, $user->id);
            } catch (InvalidArgumentException $e) {
                $this->error('Questões: ' . $e->getMessage());
                return self::FAILURE;
            }
            $this->info("Questões: {$q['novas']} novas, {$q['atualizadas']} atualizadas, {$q['puladas']} puladas (incompletas).");
            if ($q['sem_ponto']) {
                $this->warn('Sem ponto do resumo ligado: ' . implode(', ', $q['sem_ponto']));
            }
        }

        return self::SUCCESS;
    }
}
