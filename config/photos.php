<?php

$defaultPaths = env('PHOTOS_PATHS');
if ($defaultPaths) {
    $pathsArray = array_filter(array_map('trim', explode(';', $defaultPaths)));
} else {
    $single = env('PHOTOS_PATH', 'F:\\Torrents\\Concluidos');
    $pathsArray = array_filter(array_map('trim', explode(';', $single)));
}

return [
    'paths' => $pathsArray,
    'path' => env('PHOTOS_PATH', 'F:\\Torrents\\Concluidos'),
    'cache_path' => env('PHOTOS_CACHE_PATH', storage_path('app/photos_cache.json')),
];
