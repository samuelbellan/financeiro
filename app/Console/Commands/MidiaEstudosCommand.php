<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Gera os PNGs das mídias do Instagram a partir de storage/app/midias/{pasta}/dados.json.
 *
 * O dados.json é montado pela skill /post-estudos: { tipo, handle, titulo, subtitulo, selo, app (saída do
 * estudos:resumo), guruja (opcional), destaques, legenda }. Cada slide vira um HTML estático e o Edge/Chrome
 * headless tira a foto no tamanho exato. Ver docs/sabatina-2026-11-08-projecao-e-midias.md.
 */
class MidiaEstudosCommand extends Command
{
    protected $signature = 'estudos:midia
                            {pasta : Pasta dentro de storage/app/midias}
                            {--sem-png : Só gera os HTML (útil para testes)}
                            {--navegador= : Caminho do msedge.exe/chrome.exe}';

    protected $description = 'Gera as imagens (PNG) do post de estudos a partir do dados.json da pasta.';

    /** slide => [largura, altura] */
    public const SLIDES = [
        'semanal' => ['capa' => [1080, 1350], 'materias' => [1080, 1350], 'questoes' => [1080, 1350], 'story-semanal' => [1080, 1920]],
        'diario' => ['story-diario' => [1080, 1920]],
    ];

    public function handle(): int
    {
        $pasta = $this->argument('pasta');
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $pasta)) {
            $this->error('Nome de pasta inválido.');
            return self::FAILURE;
        }
        $dir = storage_path('app/midias/' . $pasta);
        $file = $dir . '/dados.json';
        if (!is_file($file)) {
            $this->error("Não encontrei {$file}");
            return self::FAILURE;
        }
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data) || !isset(self::SLIDES[$data['tipo'] ?? null], $data['app']['total_min'])) {
            $this->error('dados.json inválido: precisa de "tipo" (semanal|diario) e "app" (saída do estudos:resumo).');
            return self::FAILURE;
        }
        $slides = self::SLIDES[$data['tipo']];
        if (empty($data['guruja']['disciplinas'])) {
            if ($data['tipo'] === 'semanal') {
                $this->error('O post semanal precisa dos dados da Guruja ("guruja.disciplinas"). Não gero carrossel incompleto.');
                return self::FAILURE;
            }
            $data['guruja'] = null;
        }
        $data['handle'] ??= '@samubellan';

        $browser = $this->option('sem-png') ? null : $this->browser();
        if (!$this->option('sem-png') && !$browser) {
            $this->error('Não encontrei o Edge nem o Chrome. Informe --navegador=caminho.');
            return self::FAILURE;
        }

        $n = 0;
        foreach ($slides as $slide => [$w, $h]) {
            $n++;
            $base = sprintf('%02d-%s', $n, $slide);
            $html = $dir . "/{$base}.html";
            file_put_contents($html, view('estudos.midia.slide', ['d' => $data, 'slide' => $slide, 'w' => $w, 'h' => $h])->render());
            if ($browser) {
                $png = $dir . "/{$base}.png";
                $process = new Process([
                    $browser, '--headless=new', '--disable-gpu', '--hide-scrollbars', '--force-device-scale-factor=1',
                    "--window-size={$w},{$h}", '--virtual-time-budget=4000', "--screenshot={$png}",
                    'file:///' . str_replace('\\', '/', $html),
                ]);
                $process->setTimeout(60)->run();
                if (!is_file($png)) {
                    $this->error("Falha ao gerar {$base}.png: " . $process->getErrorOutput());
                    return self::FAILURE;
                }
                $this->line("✓ {$png}");
            }
        }
        if (!empty($data['legenda'])) {
            file_put_contents($dir . '/legenda.txt', $data['legenda']);
            $this->line('✓ ' . $dir . '/legenda.txt');
        }
        return self::SUCCESS;
    }

    protected function browser(): ?string
    {
        $candidates = array_filter([
            $this->option('navegador'),
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
        ]);
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }
}
