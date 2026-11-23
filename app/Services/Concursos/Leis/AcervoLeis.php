<?php

namespace App\Services\Concursos\Leis;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Leis do projeto ctresb/leis (Markdown com front matter YAML; artigos em linhas "**Art. 98.** …").
 * Baixa só o que está no catálogo (config concursos.leis.catalogo), guarda em storage/concursos/leis
 * e quebra o texto em artigos com âncoras estáveis ("art-98", "art-156-a", "adct-art-136").
 */
class AcervoLeis
{
    /** @var array<string, array{meta: array<string, string>, blocos: list<array{tipo: string, ancora: ?string, rotulo: ?string, md: string}>}> */
    private array $memoria = [];

    /** @return array<string, array{sigla: string, nome: string, path: string, de?: string, prefixo?: string}> */
    public function catalogo(): array
    {
        return config('concursos.leis.catalogo', []);
    }

    public function existe(string $lei): bool
    {
        return isset($this->catalogo()[$lei]);
    }

    /** Lei "dona" do arquivo (o ADCT mora no arquivo da CF). */
    public function arquivoDe(string $lei): string
    {
        return $this->catalogo()[$lei]['de'] ?? $lei;
    }

    public function baixada(string $lei): bool
    {
        return is_file($this->caminhoLocal($this->arquivoDe($lei)));
    }

    public function urlFonte(string $lei): string
    {
        return config('concursos.leis.web') . $this->catalogo()[$lei]['path'];
    }

    /** Baixa (ou atualiza) o Markdown da lei. Devolve o tamanho em bytes. */
    public function baixar(string $lei, bool $forcar = false): int
    {
        $lei = $this->arquivoDe($lei);
        $destino = $this->caminhoLocal($lei);
        if (!$forcar && is_file($destino)) {
            return filesize($destino);
        }

        $url = config('concursos.leis.raw') . $this->catalogo()[$lei]['path'];
        $resp = Http::timeout(90)->retry(2, 1000, throw: false)->get($url);
        if (!$resp->successful() || trim($resp->body()) === '') {
            throw new RuntimeException("Não consegui baixar {$url} (HTTP {$resp->status()}).");
        }

        File::ensureDirectoryExists(dirname($destino));
        File::put($destino, $resp->body());
        unset($this->memoria[$lei]);

        return strlen($resp->body());
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
        $prefixo = $this->catalogo()[$lei]['prefixo'] ?? null;

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
        return ($this->catalogo()[$lei]['prefixo'] ?? '') . 'art-' . Str::lower($artigo);
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

    private function caminhoLocal(string $lei): string
    {
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
