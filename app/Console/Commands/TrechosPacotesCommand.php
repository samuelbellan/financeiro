<?php

namespace App\Console\Commands;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialQuestao;
use App\Models\User;
use App\Services\Concursos\TrechosErrados;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Gera os pacotes para o Gemini (chat) apontar o trecho errado de cada alternativa, sem esperar o
 * primeiro erro no app. Só entram questões ainda sem trechos_errados. Depois:
 *   php artisan concursos:trechos-importar storage/concursos/trechos/respostas
 * Prompt: resources/schemas/concursos/prompts/gemini-trechos-errados.md
 */
class TrechosPacotesCommand extends Command
{
    protected $signature = 'concursos:trechos-pacotes {material? : slug (padrão: todos os materiais)}
        {--lote=50 : questões por pacote}
        {--fila : só as que estão na revisão (caixas 1 a 3)}
        {--user= : id ou e-mail do dono (padrão: o dono do sistema)}';

    protected $description = 'Gera pacotes JSON de questões para o Gemini marcar os trechos errados das alternativas.';

    public function handle(): int
    {
        $quem = $this->option('user');
        $user = $quem === null
            ? User::where('is_owner', true)->orderBy('id')->first()
            : User::where(ctype_digit((string) $quem) ? 'id' : 'email', $quem)->first();
        $materiais = $user ? Material::where('user_id', $user->id)
            ->when($this->argument('material'), fn ($q, $slug) => $q->where('slug', $slug))->pluck('id') : collect();
        if ($materiais->isEmpty()) {
            $this->error('Material ou usuário não encontrado.');

            return self::FAILURE;
        }

        $questoes = MaterialQuestao::whereIn('material_id', $materiais)->whereNull('trechos_errados')
            ->when($this->option('fila'), fn ($q) => $q->whereBetween('caixa', [1, 3]))
            ->orderBy('material_id')->orderBy('secao_id')->orderBy('ordem')->orderBy('id')->get()
            ->filter(fn ($q) => TrechosErrados::textosErrados($q) !== [])
            ->map(fn ($q) => array_filter([
                'id' => $q->id,
                'tipo' => $q->tipo,
                'gabarito' => $q->tipo === 'CE' ? 'Errado' : $q->gabarito,
                'enunciado' => $q->enunciado,
                'alternativas' => $q->tipo === 'CE' ? null : array_map(fn ($a) => ['letra' => $a['letra'], 'texto' => $a['texto']], $q->alternativas ?? []),
                'erradas' => $q->tipo === 'CE' ? null : array_keys(TrechosErrados::textosErrados($q)),
            ], fn ($v) => $v !== null))->values();

        if ($questoes->isEmpty()) {
            $this->info('Nenhuma questão sem marcação.');

            return self::SUCCESS;
        }

        $dir = storage_path('concursos/trechos');
        File::ensureDirectoryExists($dir . '/respostas');
        foreach (File::glob($dir . '/trechos-lote-*.json') as $velho) {
            File::delete($velho); // pacotes de uma rodada anterior: as já importadas não voltam
        }
        $lotes = $questoes->chunk(max(1, (int) $this->option('lote')));
        foreach ($lotes as $n => $lote) {
            $num = $n + 1;
            File::put(sprintf('%s/trechos-lote-%03d.json', $dir, $num), json_encode(
                ['lote' => $num, 'questoes' => $lote->values()],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));
        }

        $this->info("{$questoes->count()} questões em {$lotes->count()} pacote(s) em {$dir}");
        $this->line('Prompt: resources/schemas/concursos/prompts/gemini-trechos-errados.md');
        $this->line("Salve cada resposta do Gemini em {$dir}" . DIRECTORY_SEPARATOR . 'respostas e rode: php artisan concursos:trechos-importar');

        return self::SUCCESS;
    }
}
