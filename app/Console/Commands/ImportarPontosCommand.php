<?php

namespace App\Console\Commands;

use App\Models\Concursos\Material;
use App\Models\User;
use App\Services\Concursos\MaterialImportador;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Importa os pontos de resumo de um arquivo de partes (lista de seções com pontos) sem reconstruir o
 * material: só as seções do arquivo mudam, e as questões listadas em cada ponto passam a apontar para ele.
 *   php artisan concursos:importar-pontos dpen storage/concursos/materiais/dpen-partes/p04.json
 */
class ImportarPontosCommand extends Command
{
    protected $signature = 'concursos:importar-pontos {material : slug (dpen, dtrib…)} {arquivo : JSON com [{"chave": "c04", "pontos": [...]}]}
        {--user= : id ou e-mail do dono (padrão: o dono do sistema)}';

    protected $description = 'Importa os pontos de resumo de algumas seções de um material e liga as questões a eles.';

    public function handle(MaterialImportador $importador): int
    {
        $quem = $this->option('user');
        $user = $quem === null
            ? User::where('is_owner', true)->orderBy('id')->first()
            : User::where(ctype_digit((string) $quem) ? 'id' : 'email', $quem)->first();
        $material = $user ? Material::where('user_id', $user->id)->where('slug', $this->argument('material'))->first() : null;
        if (! $material) {
            $this->error('Material ou usuário não encontrado.');

            return self::FAILURE;
        }
        $partes = is_file($this->argument('arquivo')) ? json_decode((string) file_get_contents($this->argument('arquivo')), true) : null;
        if (! is_array($partes) || ! array_is_list($partes)) {
            $this->error('O arquivo tem de ser uma lista JSON de seções: [{"chave": "c04", "pontos": [...]}].');

            return self::FAILURE;
        }
        try {
            $r = $importador->importarPontos($material, $partes);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("{$material->titulo}: {$r['secoes']} seção(ões), {$r['novos']} pontos novos, {$r['atualizados']} atualizados, {$r['ligadas']} questões ligadas.");

        return self::SUCCESS;
    }
}
