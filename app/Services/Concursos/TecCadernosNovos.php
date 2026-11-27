<?php

namespace App\Services\Concursos;

use App\Models\Concursos\Material;
use App\Models\Concursos\MaterialQuestao;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Cadernos do TEC que chegaram em storage/concursos/tec/<subpasta>/ (pelo vigiar_tec.js) e ainda não
 * entraram no material. Importa as questões (certas e erradas) na seção do número do caderno e lista o
 * que falta um agente escrever: pontos de resumo para as erradas e grifos para todas.
 *
 * O registro do que já foi importado fica em storage/concursos/tec/importados.json; os cadernos que já
 * estavam nas pastas quando ele foi criado entram como "antigos" (sem pendências).
 */
class TecCadernosNovos
{
    public function __construct(private MaterialQuestoesImportador $importador, private Livro\ConversorLivro $conversor)
    {
    }

    public function raiz(): string
    {
        return storage_path('concursos/tec');
    }

    public function arquivoRegistro(): string
    {
        return $this->raiz() . DIRECTORY_SEPARATOR . 'importados.json';
    }

    /** @return array<string, array> id do caderno → dados do registro */
    public function registro(): array
    {
        $f = $this->arquivoRegistro();

        return is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    }

    private function salvarRegistro(array $reg): void
    {
        ksort($reg);
        File::put($this->arquivoRegistro(), json_encode($reg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @return list<array{id: string, subpasta: string, arquivo: string}> cadernos nas subpastas configuradas */
    public function cadernosNasPastas(): array
    {
        $lista = [];
        foreach (array_keys(config('concursos.tec.subpastas')) as $sub) {
            foreach (glob($this->raiz() . "/{$sub}/*.json") ?: [] as $f) {
                $lista[] = ['id' => explode('-', basename($f), 2)[0], 'subpasta' => $sub, 'arquivo' => $f];
            }
        }

        return $lista;
    }

    /** Primeira vez: tudo o que já está nas pastas foi importado à mão antes. */
    public function criarRegistroSeFaltar(): ?int
    {
        if (is_file($this->arquivoRegistro())) {
            return null;
        }
        $reg = [];
        foreach ($this->cadernosNasPastas() as $c) {
            $reg[$c['id']] = ['subpasta' => $c['subpasta'], 'arquivo' => basename($c['arquivo']), 'antigo' => true];
        }
        $this->salvarRegistro($reg);

        return count($reg);
    }

    /** @return list<array{id: string, subpasta: string, arquivo: string}> */
    public function novos(): array
    {
        $reg = $this->registro();

        return array_values(array_filter($this->cadernosNasPastas(), fn ($c) => !isset($reg[$c['id']])));
    }

    /**
     * Importa um caderno novo: converte com o converter_tec.py --todas (só esse caderno), importa
     * certas e erradas na seção do número dele e registra.
     *
     * @return array{material: string, secao: string, caderno: string, novas: int, certas: int, existentes: int, secao_criada: bool}
     */
    public function importar(array $c, int $userId): array
    {
        $conf = config("concursos.tec.subpastas.{$c['subpasta']}");
        $material = Material::where('user_id', $userId)->where('slug', $conf['material'])->first();
        if (!$material) {
            throw new RuntimeException("Material \"{$conf['material']}\" não existe: crie-o antes (concursos:importar-material).");
        }
        $dados = json_decode((string) file_get_contents($c['arquivo']), true);
        if (!preg_match('/C(\d{2})/', $dados['caderno'] ?? '', $m)) {
            throw new RuntimeException("Caderno sem número Cnn no nome: {$dados['caderno']}");
        }

        $todas = $this->converter($c['arquivo'], $conf['material']);
        // Título e origem da seção, caso ela não exista ainda (o importador só usa ao criar).
        $resolvidas = array_values(array_filter($dados['questoes'], fn ($q) => $q['resolvida'] && empty($q['anulada'])));
        $erros = count(array_filter($resolvidas, fn ($q) => $q['errou']));
        $assuntos = array_count_values(array_filter(array_map(fn ($q) => explode(', ', $q['assunto'] ?? '')[0], $resolvidas)));
        arsort($assuntos);
        $assunto = (string) array_key_first($assuntos);
        $total = count($resolvidas);
        foreach ($todas['secoes'] as &$s) {
            $s['titulo'] = "C{$m[1]}" . ($assunto !== '' ? " · {$assunto}" : '');
            $s['origem'] = sprintf('1 caderno(s) · %d questões · %d acertos (%d%%) · %d erros', $total, $total - $erros, $total ? round(100 * ($total - $erros) / $total) : 0, $erros);
        }
        unset($s);

        $r = $this->importador->importarTodas($todas, $userId, $material->slug, $conf['prefixo']);
        $this->conversor->converter($material->fresh());

        $reg = $this->registro();
        $reg[$c['id']] = [
            'subpasta' => $c['subpasta'],
            'arquivo' => basename($c['arquivo']),
            'caderno' => $dados['caderno'],
            'material' => $material->slug,
            'secao' => $conf['prefixo'] . 'c' . $m[1],
            'importado_em' => now()->toDateTimeString(),
        ];
        $this->salvarRegistro($reg);

        return ['material' => $material->slug, 'secao' => $conf['prefixo'] . 'c' . $m[1], 'caderno' => $dados['caderno'],
            'novas' => $r['novas'], 'certas' => $r['certas'], 'existentes' => $r['existentes'], 'secao_criada' => $r['secoes_criadas'] > 0];
    }

    private function converter(string $arquivo, string $slug): array
    {
        $tmp = storage_path('app/tec-novos-' . uniqid());
        File::ensureDirectoryExists($tmp . '/in');
        try {
            File::copy($arquivo, $tmp . '/in/' . basename($arquivo));
            $python = config('concursos.python');
            $python = is_file($python) ? $python : 'python';
            $p = Process::timeout(120)->run([$python, '-I', '-X', 'utf8', base_path('scripts/concursos/converter_tec.py'),
                $tmp . '/in', '--slug', $slug, '--titulo', '-', '--disciplina', '-', '--saida', $tmp, '--todas']);
            if (!$p->successful() || !is_file("{$tmp}/{$slug}-todas.json")) {
                throw new RuntimeException('converter_tec.py falhou: ' . trim($p->errorOutput() ?: $p->output()));
            }

            return json_decode((string) file_get_contents("{$tmp}/{$slug}-todas.json"), true);
        } finally {
            File::deleteDirectory($tmp);
        }
    }

    /**
     * O que falta nos cadernos importados por aqui: erradas sem ponto de resumo e, nas matérias que
     * têm grifos (storage/concursos/grifos/<slug>), questões sem linha no .jsonl.
     *
     * @return list<array{id: string, caderno: string, material: string, secao: string, arquivo: string, sem_ponto: list<string>, sem_grifo: list<string>|null, grifos: string|null}>
     */
    public function pendencias(int $userId): array
    {
        $saida = [];
        $grifados = [];
        foreach ($this->registro() as $id => $r) {
            if (!empty($r['antigo'])) {
                continue;
            }
            $arquivo = $this->raiz() . "/{$r['subpasta']}/{$r['arquivo']}";
            if (!is_file($arquivo)) {
                continue;
            }
            $dados = json_decode((string) file_get_contents($arquivo), true);
            $material = Material::where('user_id', $userId)->where('slug', $r['material'])->first();
            $erradas = array_map(fn ($q) => (string) $q['id'], array_filter($dados['questoes'], fn ($q) => $q['resolvida'] && $q['errou'] && empty($q['anulada'])));
            $semPonto = $material && $erradas
                ? MaterialQuestao::where('material_id', $material->id)->whereIn('id_externo', $erradas)->whereNull('ponto_id')->pluck('id_externo')->map(fn ($x) => (string) $x)->values()->all()
                : [];

            $pastaGrifos = storage_path("concursos/grifos/{$r['material']}");
            $semGrifo = null;
            if (is_dir($pastaGrifos)) {
                $grifados[$r['material']] ??= $this->idsComGrifo($pastaGrifos);
                $semGrifo = array_values(array_diff(array_unique(array_map(fn ($q) => (string) $q['id'], $dados['questoes'])), $grifados[$r['material']]));
            }
            if ($semPonto || $semGrifo) {
                $saida[] = ['id' => (string) $id, 'caderno' => $r['caderno'] ?? $r['arquivo'], 'material' => $r['material'], 'secao' => $r['secao'],
                    'arquivo' => "storage/concursos/tec/{$r['subpasta']}/{$r['arquivo']}", 'sem_ponto' => $semPonto, 'sem_grifo' => $semGrifo,
                    'grifos' => is_dir($pastaGrifos) ? "storage/concursos/grifos/{$r['material']}/" . preg_replace('/\.json$/', '.jsonl', $r['arquivo']) : null];
            }
        }

        return $saida;
    }

    /** @return list<string> */
    private function idsComGrifo(string $pasta): array
    {
        $ids = [];
        foreach (glob($pasta . '/*.jsonl') ?: [] as $f) {
            foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linha) {
                $j = json_decode($linha, true);
                if (isset($j['id'])) {
                    $ids[] = (string) $j['id'];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /** Escreve storage/concursos/tec/PENDENTES.md, que é o que o agente (Antigravity) lê. */
    public function escreverPendencias(array $pend): string
    {
        $f = $this->raiz() . DIRECTORY_SEPARATOR . 'PENDENTES.md';
        $l = ['# Cadernos do TEC com pendências', '', 'Gerado por `php artisan concursos:tec-novos` em ' . now()->format('d/m/Y H:i') . '.',
            'Siga scripts/concursos/PROMPT-antigravity-cadernos-novos.md.', ''];
        if (!$pend) {
            $l[] = 'Nada pendente.';
        }
        foreach ($pend as $p) {
            $l[] = "## {$p['material']} · {$p['caderno']} (caderno {$p['id']})";
            $l[] = '';
            $l[] = "- Seção do material: `{$p['secao']}`";
            $l[] = "- Caderno: `{$p['arquivo']}`";
            if ($p['sem_ponto']) {
                $l[] = '- Erradas sem ponto de resumo (' . count($p['sem_ponto']) . '): ' . implode(', ', $p['sem_ponto']);
                $l[] = "- Pontos: novo arquivo em `storage/concursos/materiais/{$p['material']}-partes/`";
            }
            if ($p['sem_grifo']) {
                $l[] = '- Questões sem grifo (' . count($p['sem_grifo']) . '): ' . implode(', ', $p['sem_grifo']);
                $l[] = "- Grifos: `{$p['grifos']}`";
            }
            $l[] = '';
        }
        File::put($f, implode("\n", $l));

        return $f;
    }
}
