<?php

namespace App\Console\Commands;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialQuestao;
use App\Models\User;
use App\Services\Concursos\TrechosErrados;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Importa as respostas do Gemini aos pacotes de concursos:trechos-pacotes. Aceita arquivos ou pastas
 * (padrão: storage/concursos/trechos/respostas). Cada trecho é conferido contra o texto da
 * alternativa: o que não existir nela é descartado. Reimportar sobrescreve a marcação da questão.
 */
class TrechosImportarCommand extends Command
{
    protected $signature = 'concursos:trechos-importar {caminhos?* : arquivos .json ou pastas}
        {--user= : id ou e-mail do dono (padrão: o dono do sistema)}';

    protected $description = 'Importa as marcações de trechos errados geradas pelo Gemini.';

    public function handle(): int
    {
        $quem = $this->option('user');
        $user = $quem === null
            ? User::where('is_owner', true)->orderBy('id')->first()
            : User::where(ctype_digit((string) $quem) ? 'id' : 'email', $quem)->first();
        if (!$user) {
            $this->error('Usuário não encontrado.');

            return self::FAILURE;
        }
        $materiais = Material::where('user_id', $user->id)->pluck('id');

        $arquivos = [];
        foreach ($this->argument('caminhos') ?: [storage_path('concursos/trechos/respostas')] as $c) {
            $arquivos = array_merge($arquivos, is_dir($c) ? File::glob(rtrim($c, '/\\') . '/*.json') : [$c]);
        }
        if (!$arquivos) {
            $this->error('Nenhum arquivo .json encontrado.');

            return self::FAILURE;
        }

        $salvas = $semQuestao = $propostos = $aceitos = 0;
        foreach ($arquivos as $arq) {
            // Tolera o bloco ```json que o chat às vezes põe em volta.
            $txt = preg_replace('/^\s*```(?:json)?\s*|\s*```\s*$/', '', (string) @file_get_contents($arq));
            $json = json_decode($txt, true);
            if (!is_array($json['questoes'] ?? null)) {
                $this->warn(basename($arq) . ': não é um JSON com "questoes".');
                continue;
            }
            foreach ($json['questoes'] as $r) {
                $q = MaterialQuestao::whereIn('material_id', $materiais)->find((int) ($r['id'] ?? 0));
                if (!$q || !is_array($r['itens'] ?? null)) {
                    $semQuestao++;
                    continue;
                }
                $propostos += collect($r['itens'])->sum(fn ($i) => count((array) ($i['trechos'] ?? [])));
                $aceitos += collect(TrechosErrados::aplicar($q, $r['itens']))->sum(fn ($i) => count($i['trechos']));
                $salvas++;
            }
        }

        $this->info("{$salvas} questões marcadas; {$aceitos} de {$propostos} trechos conferem com o texto.");
        if ($semQuestao) {
            $this->warn("{$semQuestao} entrada(s) ignorada(s): id desconhecido ou sem \"itens\".");
        }
        $restam = MaterialQuestao::whereIn('material_id', $materiais)->whereNull('trechos_errados')->count();
        $this->line("Sem marcação ainda: {$restam} (inclui as que não têm alternativa errada a marcar).");

        return self::SUCCESS;
    }
}
