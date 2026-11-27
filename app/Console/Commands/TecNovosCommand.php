<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Concursos\TecCadernosNovos;
use Illuminate\Console\Command;
use Throwable;

/**
 * Importa os cadernos novos que o vigiar_tec.js gravou em storage/concursos/tec/<subpasta>/ e escreve
 * storage/concursos/tec/PENDENTES.md (erradas sem ponto e questões sem grifo), que o Antigravity lê:
 *   php artisan concursos:tec-novos
 *   php artisan concursos:tec-novos --pendencias   (só refaz a lista, sem importar)
 */
class TecNovosCommand extends Command
{
    protected $signature = 'concursos:tec-novos
        {--pendencias : só recalcula o PENDENTES.md}
        {--marcar-existentes : primeira vez: registra os cadernos que já estão nas pastas como importados}
        {--user= : id ou e-mail do dono (padrão: o dono do sistema)}';

    protected $description = 'Importa os cadernos novos do TEC para o material da matéria e lista o que falta de resumo e grifos.';

    public function handle(TecCadernosNovos $tec): int
    {
        if (! config('concursos.enabled')) {
            $this->error('Módulo de concursos desligado (CONCURSOS_ENABLED=false).');

            return self::FAILURE;
        }
        $quem = $this->option('user');
        $user = $quem === null
            ? User::where('is_owner', true)->orderBy('id')->first()
            : User::where(ctype_digit((string) $quem) ? 'id' : 'email', $quem)->first();
        if (! $user) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }

        if ($this->option('marcar-existentes')) {
            $n = $tec->criarRegistroSeFaltar();
            $this->line($n === null ? 'O registro já existe: nada mudou.' : "Registro criado: {$n} cadernos que já estavam nas pastas contam como importados.");

            return self::SUCCESS;
        }
        if (! is_file($tec->arquivoRegistro())) {
            $this->error('Falta o registro do que já foi importado (' . $tec->arquivoRegistro() . '). Antes de baixar cadernos novos, rode uma vez: php artisan concursos:tec-novos --marcar-existentes');

            return self::FAILURE;
        }

        $falhas = 0;
        if (! $this->option('pendencias')) {
            $novos = $tec->novos();
            if (! $novos) {
                $this->line('Nenhum caderno novo nas pastas.');
            }
            foreach ($novos as $c) {
                try {
                    $r = $tec->importar($c, $user->id);
                    $this->info("{$r['material']} {$r['secao']} · {$r['caderno']}: {$r['novas']} questões novas ({$r['certas']} certas), {$r['existentes']} já existiam"
                        . ($r['secao_criada'] ? ', seção nova' : '') . '.');
                } catch (Throwable $e) {
                    $falhas++;
                    $this->error(basename($c['arquivo']) . ': ' . $e->getMessage());
                }
            }
        }

        $pend = $tec->pendencias($user->id);
        $arquivo = $tec->escreverPendencias($pend);
        $semPonto = array_sum(array_map(fn ($p) => count($p['sem_ponto']), $pend));
        $semGrifo = array_sum(array_map(fn ($p) => count($p['sem_grifo'] ?? []), $pend));
        $this->line($pend
            ? "Pendências: " . count($pend) . " caderno(s), {$semPonto} erradas sem ponto, {$semGrifo} questões sem grifo. Lista em {$arquivo}"
            : 'Nada pendente.');

        return $falhas ? self::FAILURE : self::SUCCESS;
    }
}
