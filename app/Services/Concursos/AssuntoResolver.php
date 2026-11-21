<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Assunto;
use App\Models\Concursos\Disciplina;

/**
 * Encontra (ou cria) o assunto canônico. Regras:
 *  - Com disciplina: (disciplina, nome) exatos do TEC. Se o assunto já existia "a confirmar"
 *    (veio só pelo nome, do edital), ele é ADOTADO pela disciplina real em vez de duplicado.
 *  - Só com o nome (edital gerado sem a disciplina): usa o assunto com esse nome se houver
 *    exatamente um; senão cria sob a disciplina provisória "(disciplina a confirmar)".
 *  - Assunto unido na curadoria (unido_em_id) devolve o assunto de destino.
 */
class AssuntoResolver
{
    public const DISCIPLINA_A_CONFIRMAR = '(disciplina a confirmar)';

    /** Assuntos criados durante esta execução, para o resumo da importação. */
    public array $criados = [];

    /** Assuntos que receberam a disciplina real nesta execução. */
    public array $adotados = [];

    /** Nomes que existem em mais de uma disciplina (o vínculo não foi feito). */
    public array $ambiguos = [];

    public function porDisciplina(string $disciplina, string $nome): Assunto
    {
        $disc = Disciplina::firstOrCreate(['nome' => $disciplina]);
        $assunto = Assunto::where('disciplina_id', $disc->id)->where('nome', $nome)->first();

        if (!$assunto && $disciplina !== self::DISCIPLINA_A_CONFIRMAR) {
            $provisorio = Assunto::where('nome', $nome)
                ->whereHas('disciplina', fn ($q) => $q->where('nome', self::DISCIPLINA_A_CONFIRMAR))
                ->first();
            if ($provisorio) {
                $provisorio->update(['disciplina_id' => $disc->id]);
                $this->adotados[] = "{$disciplina} - {$nome}";
                $assunto = $provisorio;
            }
        }

        if (!$assunto) {
            $assunto = Assunto::create(['disciplina_id' => $disc->id, 'nome' => $nome]);
            $this->criados[] = "{$disciplina} - {$nome}";
        }

        return $this->destino($assunto);
    }

    public function porNome(string $nome): ?Assunto
    {
        $existentes = Assunto::where('nome', $nome)->get();

        if ($existentes->count() > 1) {
            $this->ambiguos[] = $nome;

            return null;
        }
        if ($existentes->count() === 1) {
            return $this->destino($existentes->first());
        }

        return $this->porDisciplina(self::DISCIPLINA_A_CONFIRMAR, $nome);
    }

    private function destino(Assunto $assunto): Assunto
    {
        return $assunto->unido_em_id ? Assunto::findOrFail($assunto->unido_em_id) : $assunto;
    }
}
