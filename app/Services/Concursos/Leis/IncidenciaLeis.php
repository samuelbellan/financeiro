<?php

namespace App\Services\Concursos\Leis;

use App\Models\Concursos\Questao;
use App\Models\Concursos\QuestaoClassificacao;
use App\Models\Concursos\Tentativa;

/**
 * "Cai na prova": quantas questões do banco cobram cada artigo de uma lei (incidência, igual para
 * todos os alunos) e quantas delas o aluno já errou (pessoal).
 *
 * A fonte é QuestaoClassificacao.dispositivos, vindo da engenharia reversa, no formato
 * "lei-8112-1990/art-26/par-4" ou "cf-1988/art-37/inc-XVI/ali-b". O prefixo vira a sigla do
 * acervo (lei8112, cf, ou a sigla do catálogo cujo arquivo é aquela norma) e o resto é agregado no
 * artigo, guardando a quebra por parágrafo/inciso para o detalhe.
 */
class IncidenciaLeis
{
    /** Prefixo do slug do dispositivo → prefixo da sigla dinâmica do acervo. */
    private const TIPOS = ['lei' => 'lei', 'lc' => 'lcp', 'lcp' => 'lcp', 'dec' => 'dec', 'del' => 'del', 'ec' => 'emc', 'emc' => 'emc', 'mp' => 'mpv', 'mpv' => 'mpv'];

    public function __construct(private readonly AcervoLeis $acervo)
    {
    }

    /**
     * @return array<string, array{total: int, partes: array<string, int>, questoes: list<int>, tec: array<int, string>, erradas: list<int>, errei: int}>
     *         âncora do artigo → contagens (partes = rótulo do dispositivo → nº de questões)
     */
    public function daLei(string $lei, int $userId): array
    {
        $porArtigo = [];
        $classificacoes = QuestaoClassificacao::query()->whereNotNull('dispositivos')->get(['questao_id', 'dispositivos']);
        foreach ($classificacoes as $c) {
            $vistos = [];
            foreach ((array) $c->dispositivos as $d) {
                $alvo = $this->resolver((string) $d);
                if ($alvo === null || $alvo['lei'] !== $lei) {
                    continue;
                }
                $a = &$porArtigo[$alvo['ancora']];
                $a ??= ['total' => 0, 'partes' => [], 'questoes' => [], 'errei' => 0];
                if (!isset($vistos[$alvo['ancora']])) {
                    $vistos[$alvo['ancora']] = true;
                    $a['total']++;
                    $a['questoes'][] = (int) $c->questao_id;
                }
                if ($alvo['parte'] !== null) {
                    $a['partes'][$alvo['parte']] = ($a['partes'][$alvo['parte']] ?? 0) + 1;
                }
                unset($a);
            }
        }
        if (!$porArtigo) {
            return [];
        }

        $todas = array_merge(...array_column($porArtigo, 'questoes'));
        $erradas = Tentativa::query()
            ->where('user_id', $userId)
            ->where('correta', false)
            ->whereIn('questao_id', array_unique($todas))
            ->distinct()
            ->pluck('questao_id')
            ->flip();
        $tec = Questao::whereIn('id', array_unique($todas))->pluck('id_externo', 'id');
        foreach ($porArtigo as &$a) {
            $a['tec'] = array_map(fn ($id) => (string) ($tec[$id] ?? $id), array_combine($a['questoes'], $a['questoes']));
            $a['erradas'] = array_values(array_filter($a['questoes'], fn ($id) => isset($erradas[$id])));
            $a['errei'] = count($a['erradas']);
            arsort($a['partes']);
        }
        unset($a);

        return $porArtigo;
    }

    /**
     * "lei-8112-1990/art-26/par-4" → ['lei' => 'lei8112', 'ancora' => 'art-26', 'parte' => '§ 4'].
     *
     * @return array{lei: string, ancora: string, parte: ?string}|null
     */
    public function resolver(string $dispositivo): ?array
    {
        $partes = explode('/', strtolower(trim($dispositivo)));
        $norma = array_shift($partes);
        if (!$partes || !preg_match('/^([a-z]+)-(\d+)(?:-(\d{4}))?$/', $norma, $m)) {
            return null;
        }

        $adct = false;
        if (($partes[0] ?? '') === 'adct') {
            $adct = true;
            array_shift($partes);
        }
        $art = array_shift($partes);
        if (!$art || !preg_match('/^(adct-)?art-(\d+)(?:-([a-z]))?$/', $art, $am)) {
            return null;
        }
        $adct = $adct || $am[1] !== '';

        $lei = $m[1] === 'cf' ? ($adct ? 'adct' : 'cf') : $this->sigla($m[1], $m[2], $m[3] ?? null);
        if ($lei === null) {
            return null;
        }
        $ancora = ($adct ? 'adct-' : '') . 'art-' . $am[2] . (isset($am[3]) && $am[3] !== '' ? '-' . $am[3] : '');

        return ['lei' => $lei, 'ancora' => $ancora, 'parte' => $this->rotulo($partes)];
    }

    /** Sigla do acervo: a do catálogo cujo arquivo é essa norma (lei-5172-1966 → ctn) ou a dinâmica. */
    private function sigla(string $tipo, string $numero, ?string $ano): ?string
    {
        $dinamica = self::TIPOS[$tipo] ?? null;
        if ($dinamica === null) {
            return null;
        }
        foreach ($this->acervo->catalogo() as $sigla => $l) {
            $arquivo = basename($l['path'] ?? '');
            if (!isset($l['prefixo']) && preg_match('/^' . $dinamica . '-' . $numero . '-(\d{4})/', $arquivo, $f) && ($ano === null || $f[1] === $ano)) {
                return $sigla;
            }
        }

        return $dinamica . $numero;
    }

    /** ["par-5", "inc-ii", "ali-e"] → "§ 5, inc. II, al. e"; ["caput"] → "caput"; [] → null. */
    private function rotulo(array $partes): ?string
    {
        $saida = [];
        foreach ($partes as $p) {
            if ($p === 'caput') {
                $saida[] = 'caput';
            } elseif (preg_match('/^par-(\d+|unico)$/', $p, $m)) {
                $saida[] = $m[1] === 'unico' ? 'parágrafo único' : '§ ' . $m[1];
            } elseif (preg_match('/^inc-([ivxlc]+)$/', $p, $m)) {
                $saida[] = 'inc. ' . strtoupper($m[1]);
            } elseif (preg_match('/^ali-([a-z])$/', $p, $m)) {
                $saida[] = 'al. ' . $m[1];
            } elseif (preg_match('/^ite-(\d+)$/', $p, $m)) {
                $saida[] = 'item ' . $m[1];
            }
        }

        return $saida ? implode(', ', $saida) : null;
    }
}
