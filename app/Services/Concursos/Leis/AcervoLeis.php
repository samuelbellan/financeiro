<?php

namespace App\Services\Concursos\Leis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Leis do projeto ctresb/leis (Markdown com front matter YAML; artigos em linhas "**Art. 98.** …").
 *
 * De onde vem o texto, em ordem: a cópia completa do repositório (config concursos.leis.local,
 * ex.: E:\leis), o cache em storage/concursos/leis, ou o download do arquivo no GitHub.
 *
 * Siglas: as do catálogo (config concursos.leis.catalogo) e, com a cópia local, qualquer norma
 * federal por tipo+número: lei8137, lcp118, dec1171, del1598, emc132, mpv2158.
 * O texto é quebrado em artigos com âncoras estáveis ("art-98", "art-156-a", "adct-art-136").
 */
class AcervoLeis
{
    /** Prefixo da sigla dinâmica → [pasta em federal/, prefixo do arquivo, nome do tipo]. */
    public const TIPOS_FEDERAIS = [
        'lei' => ['leis-ordinarias', 'lei', 'Lei'],
        'lcp' => ['leis-complementares', 'lcp', 'LC'],
        'dec' => ['decretos', 'dec', 'Decreto'],
        'del' => ['decretos-lei', 'del', 'Decreto-Lei'],
        'emc' => ['emendas-constitucionais', 'emc', 'EC'],
        'mpv' => ['medidas-provisorias', 'mpv', 'MP'],
    ];

    /** @var array<string, array{meta: array<string, string>, blocos: list<array{tipo: string, ancora: ?string, rotulo: ?string, md: string}>}> */
    private array $memoria = [];

    /** @var array<string, array<string, string>|null> */
    private array $dinamicas = [];

    /** @return array<string, array{sigla: string, nome: string, path: string, de?: string, prefixo?: string}> */
    public function catalogo(): array
    {
        return config('concursos.leis.catalogo', []);
    }

    /** Entrada da lei (catálogo ou sigla dinâmica), ou null se não existir. */
    public function info(string $lei): ?array
    {
        return $this->catalogo()[$lei] ?? $this->dinamica($lei);
    }

    public function existe(string $lei): bool
    {
        return $this->info($lei) !== null;
    }

    /** Pasta da cópia completa do repositório, se configurada e presente. */
    public function raizLocal(): ?string
    {
        $raiz = config('concursos.leis.local');

        return $raiz && is_dir($raiz) ? rtrim($raiz, '/\\') : null;
    }

    /** Lei "dona" do arquivo (o ADCT mora no arquivo da CF). */
    public function arquivoDe(string $lei): string
    {
        return $this->info($lei)['de'] ?? $lei;
    }

    public function baixada(string $lei): bool
    {
        return is_file($this->caminhoLocal($this->arquivoDe($lei)));
    }

    public function urlFonte(string $lei): string
    {
        return config('concursos.leis.web') . $this->info($lei)['path'];
    }

    /** Baixa (ou atualiza) o Markdown da lei. Devolve o tamanho em bytes. */
    public function baixar(string $lei, bool $forcar = false): int
    {
        $lei = $this->arquivoDe($lei);
        $destino = $this->caminhoLocal($lei);
        if (!$forcar && is_file($destino)) {
            return filesize($destino);
        }

        $corpo = $this->baixarCaminho($this->info($lei)['path']);
        File::ensureDirectoryExists(dirname($destino));
        File::put($destino, $corpo);
        unset($this->memoria[$lei]);

        return strlen($corpo);
    }

    /**
     * Conteúdo de qualquer arquivo do repositório (caminho relativo à raiz, ex.: "jurisprudencia/stf/...").
     * Usa a cópia local; sem ela, baixa do GitHub e guarda em cache.
     */
    public function lerCaminho(string $path): string
    {
        $path = self::caminhoSeguro($path);
        if ($raiz = $this->raizLocal()) {
            $arquivo = $raiz . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (is_file($arquivo)) {
                return File::get($arquivo);
            }
            throw new RuntimeException("Não existe no acervo: {$path}");
        }

        $cache = rtrim(config('concursos.leis.dir'), '/\\') . DIRECTORY_SEPARATOR . '_acervo' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (!is_file($cache)) {
            File::ensureDirectoryExists(dirname($cache));
            File::put($cache, $this->baixarCaminho($path));
        }

        return File::get($cache);
    }

    /** Metadados do front matter (titulo, ementa, fonte...). */
    public function meta(string $lei): array
    {
        return $this->carregar($this->arquivoDe($lei))['meta'];
    }

    /**
     * Blocos da lei em ordem: títulos/capítulos ("titulo"), artigos ("artigo") e texto solto ("texto").
     *
     * @return list<array{tipo: string, ancora: ?string, rotulo: ?string, md: string}>
     */
    public function blocos(string $lei): array
    {
        $blocos = $this->carregar($this->arquivoDe($lei))['blocos'];
        $prefixo = $this->info($lei)['prefixo'] ?? null;

        if ($prefixo !== null) {
            // Só o trecho do ADCT (âncoras com o prefixo, mais os títulos entre elas).
            $inicio = null;
            foreach ($blocos as $i => $b) {
                if ($b['ancora'] && str_starts_with($b['ancora'], $prefixo)) {
                    $inicio = $i;
                    break;
                }
            }

            return $inicio === null ? [] : array_values(array_slice($blocos, max(0, $inicio - 1)));
        }

        // A CF sem o ADCT (que tem página própria).
        $fim = null;
        foreach ($blocos as $i => $b) {
            if ($b['ancora'] && str_starts_with($b['ancora'], 'adct-')) {
                $fim = $i;
                break;
            }
        }

        return $fim === null ? $blocos : array_slice($blocos, 0, max(0, $fim - 1));
    }

    /** Âncora de um artigo na página da lei: ("ctn", "98") → "art-98"; ("adct", "136") → "adct-art-136". */
    public function ancora(string $lei, string $artigo): string
    {
        return ($this->info($lei)['prefixo'] ?? '') . 'art-' . Str::lower($artigo);
    }

    /** @return array{lei: string, ancora: string, rotulo: string, md: string}|null */
    public function artigo(string $lei, string $artigo): ?array
    {
        $ancora = $this->ancora($lei, $artigo);
        foreach ($this->carregar($this->arquivoDe($lei))['blocos'] as $b) {
            if ($b['ancora'] === $ancora) {
                return ['lei' => $lei, 'ancora' => $ancora, 'rotulo' => $b['rotulo'], 'md' => $b['md']];
            }
        }

        return null;
    }

    /** Markdown da lei → HTML seguro (o texto vem de fora: HTML cru é escapado). */
    public function mdParaHtml(string $md): string
    {
        return Str::markdown($md, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    /** Sigla dinâmica de um arquivo do acervo federal (ex.: "federal/leis-ordinarias/1990/lei-8137-1990-12-27.md" → "lei8137"). */
    public static function siglaDoCaminho(string $path): ?string
    {
        foreach (self::TIPOS_FEDERAIS as $sigla => [$pasta, $arq]) {
            if (preg_match('#^federal/' . preg_quote($pasta, '#') . '/\d{4}/' . preg_quote($arq, '#') . '-(\d+)-\d{4}-\d{2}-\d{2}\.md$#', $path, $m)) {
                return $sigla . $m[1];
            }
        }

        return null;
    }

    /** Rejeita "..", caminhos absolutos e o que não for Markdown. */
    public static function caminhoSeguro(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, ':') || !str_ends_with($path, '.md')) {
            throw new RuntimeException('Caminho inválido.');
        }

        return $path;
    }

    /**
     * lei8137 → arquivo federal/leis-ordinarias/<ano>/lei-8137-<data>.md (só com a cópia local).
     * Números repetidos em épocas diferentes (decretos antigos): fica o mais recente.
     */
    private function dinamica(string $lei): ?array
    {
        if (array_key_exists($lei, $this->dinamicas)) {
            return $this->dinamicas[$lei];
        }
        $raiz = $this->raizLocal();
        if (!$raiz || !preg_match('/^(lei|lcp|dec|del|emc|mpv)(\d+)$/', $lei, $m)) {
            return $this->dinamicas[$lei] = null;
        }
        [$pasta, $arq, $nomeTipo] = self::TIPOS_FEDERAIS[$m[1]];
        $achados = glob($raiz . "/federal/{$pasta}/*/{$arq}-{$m[2]}-*.md") ?: [];
        if (!$achados) {
            return $this->dinamicas[$lei] = null;
        }
        rsort($achados); // nome termina na data: o mais recente primeiro
        $arquivo = str_replace('\\', '/', $achados[0]);
        preg_match('/-(\d{4})-\d{2}-\d{2}\.md$/', $arquivo, $ano);
        $numero = number_format((int) $m[2], 0, ',', '.');
        $sigla = $m[1] === 'emc' ? "EC {$m[2]}/{$ano[1]}" : "{$nomeTipo} {$numero}/{$ano[1]}";

        return $this->dinamicas[$lei] = [
            'sigla' => $sigla,
            'nome' => $sigla,
            'path' => ltrim(substr($arquivo, strlen(str_replace('\\', '/', $raiz))), '/'),
        ];
    }

    private function baixarCaminho(string $path): string
    {
        $url = config('concursos.leis.raw') . $path;
        $resp = Http::timeout(90)->retry(2, 1000, throw: false)->get($url);
        if (!$resp->successful() || trim($resp->body()) === '') {
            throw new RuntimeException("Não consegui baixar {$url} (HTTP {$resp->status()}).");
        }

        return $resp->body();
    }

    private function caminhoLocal(string $lei): string
    {
        // Com a cópia completa, lê direto dela.
        if (($raiz = $this->raizLocal()) && ($info = $this->info($lei))) {
            $noAcervo = $raiz . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $info['path']);
            if (is_file($noAcervo)) {
                return $noAcervo;
            }
        }

        return rtrim(config('concursos.leis.dir'), '/\\') . DIRECTORY_SEPARATOR . $lei . '.md';
    }

    /** @return array{meta: array<string, string>, blocos: list<array{tipo: string, ancora: ?string, rotulo: ?string, md: string}>} */
    private function carregar(string $lei): array
    {
        if (isset($this->memoria[$lei])) {
            return $this->memoria[$lei];
        }
        if (!$this->baixada($lei)) {
            $this->baixar($lei);
        }

        return $this->memoria[$lei] = self::analisar(File::get($this->caminhoLocal($lei)));
    }

    /**
     * Quebra o Markdown da lei em blocos. Público e estático para os testes usarem texto inventado.
     *
     * @return array{meta: array<string, string>, blocos: list<array{tipo: string, ancora: ?string, rotulo: ?string, md: string}>}
     */
    public static function analisar(string $md): array
    {
        $md = str_replace("\r\n", "\n", $md);
        $meta = [];
        if (preg_match('/\A---\n(.*?)\n---\n/s', $md, $m)) {
            foreach (explode("\n", $m[1]) as $linha) {
                if (preg_match('/^([a-z_]+):\s*"?(.*?)"?\s*$/', $linha, $kv)) {
                    $meta[$kv[1]] = $kv[2];
                }
            }
            $md = substr($md, strlen($m[0]));
        }

        $blocos = [];
        $atual = null;
        $prefixo = '';
        $fechar = function () use (&$atual, &$blocos) {
            if ($atual !== null && trim($atual['md']) !== '') {
                $atual['md'] = trim($atual['md']);
                $blocos[] = $atual;
            }
            $atual = null;
        };

        foreach (explode("\n", $md) as $linha) {
            if (preg_match('/^#{1,6}\s+(.*)$/u', $linha, $h)) {
                $fechar();
                if (preg_match('/ATO DAS DISPOSI..ES CONSTITUCIONAIS TRANSIT.RIAS/iu', $h[1])) {
                    $prefixo = 'adct-';
                }
                $blocos[] = ['tipo' => 'titulo', 'ancora' => null, 'rotulo' => null, 'md' => trim($linha)];

                continue;
            }
            if (preg_match('/^\*\*Art\.\s*(\d+)\s*[ºo°]?\s*(?:-\s*([A-Za-z]))?\s*\.?\s*\*\*/u', $linha, $a)) {
                $fechar();
                $num = $a[1] . (isset($a[2]) && $a[2] !== '' ? '-' . strtoupper($a[2]) : '');
                $atual = ['tipo' => 'artigo', 'ancora' => $prefixo . 'art-' . strtolower($num), 'rotulo' => 'Art. ' . $num, 'md' => $linha . "\n"];

                continue;
            }
            if ($atual === null) {
                $atual = ['tipo' => 'texto', 'ancora' => null, 'rotulo' => null, 'md' => ''];
            }
            $atual['md'] .= $linha . "\n";
        }
        $fechar();

        // Âncoras repetidas (artigo citado duas vezes, renumerações): só a primeira fica com a âncora.
        $vistas = [];
        foreach ($blocos as &$b) {
            if ($b['ancora'] !== null) {
                if (isset($vistas[$b['ancora']])) {
                    $b['ancora'] = null;
                    $b['tipo'] = 'texto';
                } else {
                    $vistas[$b['ancora']] = true;
                }
            }
        }

        return ['meta' => $meta, 'blocos' => $blocos];
    }
}
