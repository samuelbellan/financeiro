<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PhotoController;

class ScanPhotoModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:scan-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Varre os álbuns em busca de arquivos .txt biográficos e atualiza o cache com informações detalhadas dos modelos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            $this->error('Cache de fotos não encontrado. Execute a sincronização primeiro.');
            return 1;
        }

        $this->info('Iniciando varredura rápida de metadados dos modelos nos álbuns...');
        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];

        if (empty($albums)) {
            $this->warn('Nenhum álbum encontrado no cache.');
            return 0;
        }

        $controller = new PhotoController();
        $updatedCount = 0;
        $bar = $this->output->createProgressBar(count($albums));
        $bar->start();

        foreach ($albums as &$album) {
            $albumPath = $album['path'] ?? null;
            if ($albumPath && is_dir($albumPath)) {
                $models = $controller->parseModelBioFiles($albumPath);
                if (!empty($models)) {
                    $album['models'] = $models;
                    if (empty($album['model']) || $album['model'] === 'Várias') {
                        $album['model'] = implode(', ', array_filter(array_column($models, 'name')));
                    }
                    $album['model_age'] = $models[0]['age'] ?? null;
                    $album['model_country'] = $models[0]['country'] ?? null;
                    $album['model_hair'] = $models[0]['hair'] ?? null;
                    $album['model_eye'] = $models[0]['eye'] ?? null;
                    $album['model_height'] = $models[0]['height'] ?? null;
                    $album['model_weight'] = $models[0]['weight'] ?? null;
                    $album['model_breast'] = $models[0]['breast'] ?? null;
                    $album['model_measurements'] = $models[0]['measurements'] ?? null;
                    $album['model_ethnicity'] = $models[0]['ethnicity'] ?? null;
                    $album['model_bio'] = $models[0]['bio'] ?? null;
                    $updatedCount++;
                }

                // Re-parse folder details if model is empty, or album has unnormalized/hash title, or is Watch4Beauty
                $folder = $album['folder_name'] ?? '';
                if (empty($album['model']) || stripos($folder, 'watch4beauty') !== false || preg_match('/(_med|_mid|_low|_high|_large)$/i', $album['album_name'] ?? '') || preg_match('/^[a-f0-9]{4,10}_/i', $album['album_name'] ?? '')) {
                    $reflector = new \ReflectionClass(PhotoController::class);
                    $parseMethod = $reflector->getMethod('parseAlbumFolder');
                    $parseMethod->setAccessible(true);
                    $parsed = $parseMethod->invoke($controller, $albumPath, $folder, $album['file_count'] ?? 0, $album['cover_image'] ?? '');

                    if (!empty($parsed['model'])) {
                        $album['model'] = $parsed['model'];
                    }
                    if (!empty($parsed['album_name']) && $parsed['album_name'] !== $folder) {
                        $album['album_name'] = $parsed['album_name'];
                    }
                    if (!empty($parsed['site']) && $parsed['site'] !== 'Outros') {
                        $album['site'] = $parsed['site'];
                    }
                    if (!empty($parsed['date'])) {
                        $album['date'] = $parsed['date'];
                        $album['year'] = substr($parsed['date'], 0, 4);
                        $album['month'] = substr($parsed['date'], 5, 2);
                    }
                    if (!empty($parsed['photographer'])) {
                        $album['photographer'] = $parsed['photographer'];
                    }
                    $updatedCount++;
                }
            }
            $bar->advance();
        }
        unset($album);

        $bar->finish();
        $this->newLine();

        $cacheData['albums'] = $albums;
        file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));

        $this->info("Sucesso! {$updatedCount} álbuns atualizados com informações biográficas dos modelos.");
        return 0;
    }
}
