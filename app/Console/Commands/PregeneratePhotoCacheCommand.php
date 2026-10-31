<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PhotoController;

class PregeneratePhotoCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:pregenerate 
                            {--album= : ID específico do álbum para processar}
                            {--force : Forçar re-geração mesmo se já otimizado}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pré-gera miniaturas WebP (320px) no SSD local para renderizar as listas de fotos mais rápido.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            $this->error('Cache de fotos não encontrado. Execute a sincronização primeiro pela interface ou via web.');
            return 1;
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];

        if (empty($albums)) {
            $this->warn('Nenhum álbum encontrado no cache.');
            return 0;
        }

        $albumFilter = $this->option('album');
        $force = (bool) $this->option('force');

        if ($albumFilter) {
            $albums = array_filter($albums, fn($a) => $a['id'] === $albumFilter);
            if (empty($albums)) {
                $this->error("Álbum com ID '{$albumFilter}' não foi encontrado.");
                return 1;
            }
        } elseif (!$force) {
            // Only process pending by default unless force is passed
            $pendingCount = count(array_filter($albums, fn($a) => empty($a['optimized'])));
            $this->info("Total de álbuns: " . count($albums) . " | Pendentes de otimização: {$pendingCount}");
        }

        $controller = new PhotoController();
        $driver = class_exists('Imagick') ? 'Imagick (Multi-threaded WebP)' : 'GD (WebP)';
        $this->info("Iniciando motor de otimização gráfica usando: {$driver}");

        $processedAlbums = 0;
        foreach ($albums as $album) {
            if (!$force && !empty($album['optimized'])) {
                continue;
            }

            $albumPath = $album['path'];
            if (!is_dir($albumPath)) {
                $this->warn("Ignorando álbum '{$album['album_name']}': diretório não encontrado.");
                continue;
            }

            // Get safe images
            $reflector = new \ReflectionClass(PhotoController::class);
            $safeGlob = $reflector->getMethod('safeGlobImages');
            $safeGlob->setAccessible(true);
            $files = $safeGlob->invoke($controller, $albumPath);

            $this->line("\n<fg=cyan>Processando:</> {$album['album_name']} (" . count($files) . " fotos)");
            $progressBar = $this->output->createProgressBar(count($files));
            $progressBar->start();

            $getThumb = $reflector->getMethod('getOrCreateThumbnail');
            $getThumb->setAccessible(true);



            foreach ($files as $file) {
                $getThumb->invoke($controller, $file);

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->line("");

            // Update cache record
            foreach ($cacheData['albums'] as &$item) {
                if ($item['id'] === $album['id']) {
                    $item['optimized'] = true;
                    $item['optimized_at'] = date('Y-m-d H:i:s');
                    $item['optimized_count'] = count($files);
                    break;
                }
            }
            file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
            $processedAlbums++;
        }

        $this->info("\n<fg=green;options=bold>✓ Concluído com sucesso!</> {$processedAlbums} álbum(ns) pré-processado(s) para o SSD.");
        return 0;
    }
}
