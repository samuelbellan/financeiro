<?php

namespace App\Console\Commands;

use App\Models\Concursos\Material;
use App\Models\User;
use App\Services\Concursos\Livro\ConversorLivro;
use App\Services\Concursos\MaterialQuestoesImportador;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Importa todas as questões resolvidas nos cadernos (certas e erradas) para a área de Questões do material.
 *   python -I scripts/concursos/converter_tec.py storage/concursos/tec/dcon --slug dcon --titulo x --disciplina x --saida <pasta> --todas
 *   php artisan concursos:importar-todas <pasta>/dcon-todas.json
 *   php artisan concursos:importar-todas <pasta>/dtrib-todas.json --material=dtrib --prefixo-secao=t
 * Pode rodar de novo: o que já existe não duplica. O JSON fica fora do git (conteúdo do TEC).
 */
class ImportarTodasCommand extends Command
{
    protected $signature = 'concursos:importar-todas {arquivo : <slug>-todas.json do converter_tec.py --todas}
                            {--material= : slug do material (padrão: o do JSON)}
                            {--prefixo-secao= : prefixo das chaves de seção (ex.: t para os cadernos TEC do Tributário)}
                            {--user= : e-mail ou id do aluno (padrão: o dono)}';

    protected $description = 'Importa as questões certas e erradas dos cadernos do TEC para a área de Questões de um material.';

    public function handle(MaterialQuestoesImportador $importador, ConversorLivro $conversor): int
    {
        $dados = is_file($this->argument('arquivo')) ? json_decode((string) file_get_contents($this->argument('arquivo')), true) : null;
        if (!is_array($dados)) {
            $this->error('Não consegui ler o JSON.');
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

        try {
            $r = $importador->importarTodas($dados, $user->id, $this->option('material'), (string) $this->option('prefixo-secao'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        // Encaixa as novas nos capítulos do assunto (sem isso, a primeira abertura do livro faria isso).
        $material = Material::where('user_id', $user->id)->where('slug', $this->option('material') ?? $dados['material'])->first();
        $c = $conversor->converter($material);

        $this->info("{$material->titulo}: {$r['novas']} novas ({$r['certas']} certas), {$r['existentes']} já existiam, {$r['puladas']} puladas, {$r['secoes_criadas']} seções criadas; {$c['questoes']} encaixadas em capítulos ({$c['capitulos']} capítulos novos).");

        return self::SUCCESS;
    }
}
