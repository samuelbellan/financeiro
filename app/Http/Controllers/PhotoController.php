<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PhotoController extends Controller
{
    /**
     * Show the photo module login page.
     */
    public function showLogin()
    {
        // If already authenticated, redirect to gallery
        if (session('photos_authenticated')) {
            return redirect()->route('photos.index');
        }

        return view('photos.login');
    }

    /**
     * Handle the photo authentication attempt.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $expectedUser = env('PHOTOS_USER', 'nalleb');
        $expectedPass = env('PHOTOS_PASSWORD', 'Sb_692701');

        if ($request->input('username') === $expectedUser && $request->input('password') === $expectedPass) {
            session(['photos_authenticated' => true]);
            return redirect()->route('photos.index')->with('success', 'Autenticado com sucesso no visualizador.');
        }

        return back()->withErrors([
            'username' => 'Usuário ou senha incorretos para o módulo de fotos.',
        ])->withInput($request->only('username'));
    }

    /**
     * Log out of the photo module.
     */
    public function logout()
    {
        session()->forget('photos_authenticated');
        return redirect()->route('home')->with('success', 'Sessão do módulo de fotos encerrada.');
    }

    /**
     * List all mapped albums.
     */
    public function index(Request $request)
    {
        $cachePath = config('photos.cache_path');

        // Automatically sync if cache does not exist
        if (!file_exists($cachePath)) {
            $this->performSync();
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];
        $lastSync = $cacheData['last_sync'] ?? null;
        $photosPath = $cacheData['photos_path'] ?? trim(config('photos.path'), '"\'');

        // Extract list of unique sites, years/months, models, and photographers for filter selectors
        $sites = array_unique(array_column($albums, 'site'));
        sort($sites);

        $yearsMonths = [];
        foreach ($albums as $album) {
            if (!empty($album['year']) && !empty($album['month'])) {
                $yearsMonths[$album['year'] . '-' . $album['month']] = [
                    'year' => $album['year'],
                    'month' => $album['month'],
                    'label' => $this->getMonthName($album['month']) . ' de ' . $album['year']
                ];
            }
        }
        krsort($yearsMonths); // Sort chronologically descending

        // Models list
        $models = [];
        foreach ($albums as $album) {
            if (!empty($album['model'])) {
                $models[] = $album['model'];
            }
        }
        $models = array_unique($models);
        sort($models);

        // Apply filters
        $search = $request->query('search');
        $selectedSite = $request->query('site');
        $selectedDate = $request->query('date'); // formats: YYYY-MM
        $selectedModel = $request->query('model');

        $filteredAlbums = array_filter($albums, function ($album) use ($search, $selectedSite, $selectedDate, $selectedModel) {
            // Filter by Site
            if ($selectedSite && $album['site'] !== $selectedSite) {
                return false;
            }

            // Filter by Date (Year-Month)
            if ($selectedDate) {
                $albumDatePrefix = $album['year'] . '-' . $album['month'];
                if ($albumDatePrefix !== $selectedDate) {
                    return false;
                }
            }

            // Filter by Model
            if ($selectedModel && (!isset($album['model']) || $album['model'] !== $selectedModel)) {
                return false;
            }

            // Filter by Search Query
            if ($search) {
                $searchLower = strtolower($search);
                $nameMatch = str_contains(strtolower($album['album_name']), $searchLower);
                $folderMatch = str_contains(strtolower($album['folder_name']), $searchLower);
                $modelMatch = isset($album['model']) && str_contains(strtolower($album['model']), $searchLower);
                $photoMatch = isset($album['photographer']) && str_contains(strtolower($album['photographer']), $searchLower);

                if (!$nameMatch && !$folderMatch && !$modelMatch && !$photoMatch) {
                    return false;
                }
            }

            return true;
        });

        // Paginate manually or just show all if count is reasonable (e.g. 376 is small enough for grid view, but let's sort them by date descending)
        usort($filteredAlbums, function ($a, $b) {
            return strcmp($b['date'], $a['date']); // Date desc
        });

        return view('photos.index', [
            'albums' => $filteredAlbums,
            'sites' => $sites,
            'yearsMonths' => $yearsMonths,
            'models' => $models,
            'lastSync' => $lastSync,
            'photosPath' => $photosPath,
            'filters' => [
                'search' => $search,
                'site' => $selectedSite,
                'date' => $selectedDate,
                'model' => $selectedModel
            ]
        ]);
    }

    /**
     * Show album detail grid.
     */
    public function showAlbum($id)
    {
        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return redirect()->route('photos.index')->with('error', 'O cache de fotos precisa ser gerado.');
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];

        // Find album by ID
        $album = null;
        foreach ($albums as $item) {
            if ($item['id'] === $id) {
                $album = $item;
                break;
            }
        }

        if (!$album) {
            abort(404, 'Álbum não encontrado');
        }

        $albumPath = $album['path'];
        if (!is_dir($albumPath)) {
            abort(404, 'Diretório do álbum não existe localmente.');
        }

        // Get all image files inside the album safely (supports square brackets)
        $files = $this->safeGlobImages($albumPath);

        $photos = array_map(function ($filePath) {
            return basename($filePath);
        }, $files);

        return view('photos.album', [
            'album' => $album,
            'photos' => $photos
        ]);
    }

    /**
     * Sync and rebuild the photo albums cache.
     */
    public function sync(Request $request)
    {
        try {
            $customPath = $request->input('photos_path');
            $this->performSync($customPath);
            return redirect()->route('photos.index')->with('success', 'Mapeamento inteligente de fotos atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar fotos: ' . $e->getMessage());
            return redirect()->route('photos.index')->with('error', 'Erro ao sincronizar fotos: ' . $e->getMessage());
        }
    }

    /**
     * Perform the actual directory scanning and write cache.
     */
    private function performSync($customPath = null)
    {
        $cachePath = config('photos.cache_path');
        $existingAlbums = [];
        $existingPhotosPath = null;
        if (file_exists($cachePath)) {
            $oldCache = json_decode(file_get_contents($cachePath), true);
            $existingPhotosPath = $oldCache['photos_path'] ?? null;
            if (isset($oldCache['albums'])) {
                foreach ($oldCache['albums'] as $oldAlbum) {
                    $existingAlbums[$oldAlbum['path']] = $oldAlbum;
                }
            }
        }

        $basePath = $customPath ?: ($existingPhotosPath ?: config('photos.path'));
        $basePath = trim($basePath, '"\'');
        
        $albums = [];

        // 1. Scan $basePath if it exists
        if (is_dir($basePath)) {
            $basePath = realpath($basePath);
            $dir = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDir()) {
                    $path = $fileInfo->getRealPath();
                    
                    // Count only directories up to depth of 3 to avoid infinite or extremely deep loops
                    // relative depth calculation
                    $relPath = str_replace($basePath, '', $path);
                    $depth = substr_count($relPath, DIRECTORY_SEPARATOR);
                    
                    if ($depth > 3) {
                        continue;
                    }

                    // Check for images safely (supports square brackets)
                    $images = $this->safeGlobImages($path);
                    if (!empty($images)) {
                        $folderName = basename($path);
                        
                        // Look for an image containing "cover" (case-insensitive) in its filename
                        $coverImage = null;
                        foreach ($images as $img) {
                            if (stripos(basename($img), 'cover') !== false) {
                                $coverImage = $img;
                                break;
                            }
                        }
                        
                        // Fallback to the first image if no cover image is found
                        if (!$coverImage) {
                            $coverImage = $images[0];
                        }
                        
                        $albumData = $this->parseAlbumFolder($path, $folderName, count($images), $coverImage);
                        
                        // Preserve optimized state and megapixels if file count matches, plus rating, favorite, and tags
                        if (isset($existingAlbums[$path])) {
                            $oldAlbum = $existingAlbums[$path];
                            if ($oldAlbum['file_count'] === count($images) && !empty($oldAlbum['optimized'])) {
                                $albumData['optimized'] = true;
                            }
                            if (isset($oldAlbum['megapixels'])) {
                                $albumData['megapixels'] = $oldAlbum['megapixels'];
                            }
                            if (isset($oldAlbum['rating'])) {
                                $albumData['rating'] = $oldAlbum['rating'];
                            }
                            if (isset($oldAlbum['favorite'])) {
                                $albumData['favorite'] = $oldAlbum['favorite'];
                            }
                            if (isset($oldAlbum['tags'])) {
                                $albumData['tags'] = $oldAlbum['tags'];
                            }
                        }

                        // Pre-generate the album cover thumbnail so index loads immediately
                        $this->getOrCreateThumbnail($path . DIRECTORY_SEPARATOR . $albumData['cover_image']);
                        
                        $albums[] = $albumData;
                    }
                }
            }
        }

        // 2. Add C:\Users\SaMuB\Documents\financeiro\storage\model if it exists
        $localSample = 'C:\\Users\\SaMuB\\Documents\\financeiro\\storage\\model';
        if (is_dir($localSample)) {
            $images = $this->safeGlobImages($localSample);
            if (!empty($images)) {
                // Pre-generate cover thumbnail
                $this->getOrCreateThumbnail($localSample . DIRECTORY_SEPARATOR . 'home.jpg');
                $optimized = false;
                $rating = null;
                $favorite = false;
                $tags = [];
                if (isset($existingAlbums[$localSample])) {
                    $oldAlbum = $existingAlbums[$localSample];
                    if ($oldAlbum['file_count'] === count($images) && !empty($oldAlbum['optimized'])) {
                        $optimized = true;
                    }
                    $rating = $oldAlbum['rating'] ?? null;
                    $favorite = $oldAlbum['favorite'] ?? false;
                    $tags = $oldAlbum['tags'] ?? [];
                }

                $albums[] = [
                    'id' => md5($localSample),
                    'path' => $localSample,
                    'folder_name' => 'model',
                    'site' => 'Local Storage',
                    'date' => date('Y-m-d', filemtime($localSample . '/home.jpg')),
                    'year' => date('Y', filemtime($localSample . '/home.jpg')),
                    'month' => date('m', filemtime($localSample . '/home.jpg')),
                    'model' => 'Home',
                    'album_name' => 'Exemplo Home',
                    'photographer' => 'Sistema',
                    'file_count' => count($images),
                    'cover_image' => 'home.jpg',
                    'megapixels' => 24,
                    'optimized' => $optimized,
                    'rating' => $rating,
                    'favorite' => $favorite,
                    'tags' => $tags
                ];
            }
        }

        // Write cache
        $cacheData = [
            'last_sync' => date('Y-m-d H:i:s'),
            'photos_path' => $basePath,
            'albums' => $albums
        ];

        $cacheDir = dirname(config('photos.cache_path'));
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents(config('photos.cache_path'), json_encode($cacheData, JSON_PRETTY_PRINT));
    }

    /**
     * Parse folder details intelligently.
     */
    private function parseAlbumFolder($path, $folderName, $fileCount, $coverImage)
    {
        $id = md5($path);
        
        $site = 'Outros';
        $date = null;
        $model = null;
        $albumName = $folderName;
        $photographer = null;

        // Clean folder name to remove special symbols/tags for matching
        $cleanFolderName = trim($folderName);

        // Pattern A (Femjoy Style): Femjoy_2026-06-01_GETTING-HOT_SUMIKO_by-DAVID-EKMEKCI
        if (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_([a-z0-9\-]+)_([a-z0-9\-]+)_by-(.+)$/i', $cleanFolderName, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            $albumName = str_replace('-', ' ', $matches[3]);
            $model = str_replace('-', ' ', $matches[4]);
            $photographer = str_replace('-', ' ', $matches[5]);
        }
        // Pattern B (MPLStudios Style): Mplstudios_2026-06-01_Hareniks_Meringue
        elseif (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_([a-z0-9\-]+)_(.+)$/i', $cleanFolderName, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            $model = str_replace('-', ' ', $matches[3]);
            $albumName = str_replace('-', ' ', $matches[4]);
        }
        // Pattern C (MetArt Style): [MetArt.com] 26-07-01 Andrea - Morning Temptation (x141) 4032x6048
        elseif (preg_match('/^\[([a-z0-9\.]+)\]\s+(\d{2}-\d{2}-\d{2})\s+([^\-]+)\s+-\s+(.+?)(?:\s+\(x\d+\))?(?:\s+\d+x\d+)?$/i', $cleanFolderName, $matches)) {
            $site = str_replace('.com', '', $matches[1]);
            $date = '20' . $matches[2];
            $model = trim($matches[3]);
            $albumName = trim($matches[4]);
            $albumName = preg_replace('/\s*\(x\d+\).*/i', '', $albumName);
            $albumName = preg_replace('/\s*\d+x\d+.*/i', '', $albumName);
        }
        // Pattern D (Generic Site and Date): SiteName_YYYY-MM-DD_...
        elseif (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_(.+)$/i', $cleanFolderName, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            $albumName = str_replace('-', ' ', $matches[3]);
        }

        // Try to identify site name from path if still "Outros"
        if ($site === 'Outros' || $site === 'outros') {
            $lowerPath = strtolower($path);
            if (str_contains($lowerPath, 'metart')) {
                $site = 'MetArt';
            } elseif (str_contains($lowerPath, 'femjoy')) {
                $site = 'Femjoy';
            } elseif (str_contains($lowerPath, 'watch4beauty')) {
                $site = 'Watch4Beauty';
            } elseif (str_contains($lowerPath, 'wowgirls')) {
                $site = 'Wowgirls';
            } elseif (str_contains($lowerPath, 'ultrafilms')) {
                $site = 'Ultrafilms';
            } elseif (str_contains($lowerPath, 'mplstudios')) {
                $site = 'MPLStudios';
            }
        }

        // Standardize known sites
        $siteLower = strtolower($site);
        if ($siteLower === 'femjoy') $site = 'Femjoy';
        elseif ($siteLower === 'metart') $site = 'MetArt';
        elseif ($siteLower === 'mplstudios') $site = 'MPLStudios';
        elseif ($siteLower === 'watch4beauty') $site = 'Watch4Beauty';
        elseif ($siteLower === 'wowgirls') $site = 'Wowgirls';
        elseif ($siteLower === 'ultrafilms') $site = 'Ultrafilms';
        else $site = ucfirst($site);

        // Standardize Date
        if (!$date) {
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $cleanFolderName, $dMatches)) {
                $date = $dMatches[1];
            } elseif (preg_match('/(\d{2}-\d{2}-\d{2})/', $cleanFolderName, $dMatches)) {
                $date = '20' . $dMatches[1];
            } else {
                $date = date('Y-m-d', filemtime($path));
            }
        }

        // Clean model and album name (title case for model, capitalize album name)
        if ($model) {
            $model = ucwords(strtolower($model));
        }
        $albumName = ucwords(strtolower($albumName));

        // Calculate megapixels from folder name or cover image
        $megapixels = null;
        if (preg_match('/(\d+)\s*x\s*(\d+)/i', $folderName, $resMatches)) {
            $w = (int)$resMatches[1];
            $h = (int)$resMatches[2];
            if ($w > 0 && $h > 0) {
                $megapixels = round(($w * $h) / 1000000);
            }
        }
        if (!$megapixels && file_exists($coverImage)) {
            $size = @getimagesize($coverImage);
            if ($size && $size[0] > 0 && $size[1] > 0) {
                $megapixels = round(($size[0] * $size[1]) / 1000000);
            }
        }
        if (!$megapixels) {
            $megapixels = 24; // Default fallback
        }

        return [
            'id' => $id,
            'path' => $path,
            'folder_name' => $folderName,
            'site' => $site,
            'date' => $date,
            'year' => substr($date, 0, 4),
            'month' => substr($date, 5, 2),
            'model' => $model,
            'album_name' => $albumName,
            'photographer' => $photographer ? ucwords(strtolower($photographer)) : null,
            'file_count' => $fileCount,
            'cover_image' => basename($coverImage),
            'megapixels' => $megapixels,
            'optimized' => false
        ];
    }

    /**
     * Safely stream file to prevent directory traversal.
     */
    public function servePhoto(Request $request)
    {
        $path = $request->query('path');
        if (!$path) {
            abort(400, 'Caminho ausente');
        }

        $decodedPath = base64_decode($path, true);
        if ($decodedPath === false || empty($decodedPath)) {
            abort(400, 'Caminho inválido');
        }

        $decodedPath = realpath($decodedPath);
        if (!$decodedPath) {
            abort(404, 'Arquivo não encontrado');
        }

        // Load active photos path from cache to allow dynamic mapping directories
        $cachePath = config('photos.cache_path');
        $activePhotosPath = null;
        if (file_exists($cachePath)) {
            $cacheData = json_decode(file_get_contents($cachePath), true);
            $activePhotosPath = $cacheData['photos_path'] ?? null;
        }

        $basePath = $activePhotosPath ?: config('photos.path');

        // Security boundary validation
        $allowedBase = realpath(trim($basePath, '"\''));
        $allowedHome = realpath('C:\\Users\\SaMuB\\Documents\\financeiro\\storage\\model');

        $isInsideBase = ($allowedBase !== false && strpos($decodedPath, $allowedBase) === 0);
        $isInsideHome = ($allowedHome !== false && strpos($decodedPath, $allowedHome) === 0);

        if (!$isInsideBase && !$isInsideHome) {
            abort(403, 'Acesso não autorizado');
        }

        if (!is_file($decodedPath)) {
            abort(404, 'Arquivo não encontrado');
        }

        $extension = strtolower(pathinfo($decodedPath, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            abort(403, 'Tipo de arquivo não permitido');
        }

        $servePath = $decodedPath;
        $mimeType = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        // If thumbnail requested, get or generate the cached thumbnail
        if ($request->query('type') === 'thumb') {
            $servePath = $this->getOrCreateThumbnail($decodedPath);
            $mimeType = 'image/jpeg';
        }

        return response()->file($servePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Pre-generate all thumbnails for a specific album.
     */
    public function pregenerateThumbs(Request $request)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', 0);
        session_write_close();

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'ID do álbum ausente.'], 400);
        }

        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return response()->json(['success' => false, 'message' => 'Cache de fotos não encontrado. Sincronize primeiro.'], 400);
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];

        // Find album by ID
        $album = null;
        foreach ($albums as $item) {
            if ($item['id'] === $id) {
                $album = $item;
                break;
            }
        }

        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Álbum não encontrado.'], 404);
        }

        $albumPath = $album['path'];
        if (!is_dir($albumPath)) {
            return response()->json(['success' => false, 'message' => 'Diretório do álbum não existe localmente.'], 404);
        }

        // Get all image files inside the album safely
        $files = $this->safeGlobImages($albumPath);

        return response()->stream(function () use ($files, $id) {
            $total = count($files);
            $current = 0;

            foreach ($files as $file) {
                $current++;
                $this->getOrCreateThumbnail($file);

                echo json_encode([
                    'success' => true,
                    'photo' => basename($file),
                    'current' => $current,
                    'total' => $total,
                    'driver' => class_exists('Imagick') ? 'Imagick' : 'GD'
                ]) . "\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            // Update optimized flag in cache
            $cachePath = config('photos.cache_path');
            if (file_exists($cachePath)) {
                $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
                if (isset($cacheData['albums'])) {
                    foreach ($cacheData['albums'] as &$item) {
                        if ($item['id'] === $id) {
                            $item['optimized'] = true;
                            break;
                        }
                    }
                    file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get or create a resized thumbnail from a photo to reduce rendering weight.
     */
    private function getOrCreateThumbnail($originalPath)
    {
        $hash = md5($originalPath);
        $thumbDir = storage_path('app/photo_thumbnails');
        
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        
        $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . $hash . '.jpg';
        
        if (file_exists($thumbPath)) {
            return $thumbPath;
        }
        
        // 1. Try using Imagick if available (faster, lower memory, higher quality)
        if (class_exists('Imagick')) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($originalPath);
                
                // Handle transparent PNGs by flattening onto a white background
                if ($imagick->getImageAlphaChannel()) {
                    $imagick->setImageBackgroundColor('white');
                    $flat = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                    $imagick->clear();
                    $imagick->destroy();
                    $imagick = $flat;
                }
                
                $imagick->stripImage();
                $imagick->thumbnailImage(320, 320, true);
                $imagick->setImageFormat('jpeg');
                $imagick->setImageCompressionQuality(70);
                
                $imagick->writeImage($thumbPath);
                $imagick->clear();
                $imagick->destroy();
                
                return $thumbPath;
            } catch (\Exception $e) {
                Log::error('Erro ao gerar miniatura com Imagick para ' . $originalPath . ': ' . $e->getMessage() . '. Usando fallback GD.');
            }
        }
        
        // 2. Fallback to GD if Imagick is not installed/enabled or fails
        try {
            @ini_set('memory_limit', '512M');
            
            $extension = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            $image = null;
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = @imagecreatefromjpeg($originalPath);
                    break;
                case 'png':
                    $image = @imagecreatefrompng($originalPath);
                    break;
                case 'webp':
                    $image = @imagecreatefromwebp($originalPath);
                    break;
            }
            
            if (!$image) {
                return $originalPath; // Fallback to original
            }
            
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Limit thumbnails to max 320px bounding box
            $maxSize = 320;
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = (int) floor($height * ($maxSize / $width));
            } else {
                $newHeight = $maxSize;
                $newWidth = (int) floor($width * ($maxSize / $height));
            }
            
            // Prevent division by zero or negative dimensions
            $newWidth = max(1, $newWidth);
            $newHeight = max(1, $newHeight);
            
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            
            // Fill white background for transpancency conversions to jpeg
            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefill($thumb, 0, 0, $white);
            
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save thumbnail as JPEG with quality 70 to keep it very light (~15-25kb)
            imagejpeg($thumb, $thumbPath, 70);
            
            imagedestroy($image);
            imagedestroy($thumb);
            
            return $thumbPath;
        } catch (\Exception $e) {
            Log::error('Erro ao gerar miniatura para ' . $originalPath . ': ' . $e->getMessage());
            return $originalPath; // Fallback to original
        }
    }

    /**
     * Safely scans a directory for image files, immune to glob square brackets gotcha.
     */
    private function safeGlobImages($dir)
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = @scandir($dir);
        if ($files === false) {
            return [];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $images = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions)) {
                $images[] = $dir . DIRECTORY_SEPARATOR . $file;
            }
        }

        // Sort naturally
        natsort($images);
        return array_values($images);
    }

    /**
     * Helper to get month name in Portuguese.
     */
    private function getMonthName($monthNum)
    {
        return match ((int)$monthNum) {
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
            default => '',
        };
    }

    /**
     * Rate an album (1-5 stars).
     */
    public function rateAlbum(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:0|max:5',
        ]);

        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return response()->json(['success' => false, 'message' => 'Cache de fotos não encontrado.'], 400);
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $updated = false;

        if (isset($cacheData['albums'])) {
            foreach ($cacheData['albums'] as &$album) {
                if ($album['id'] === $id) {
                    $album['rating'] = $request->input('rating');
                    $updated = true;
                    break;
                }
            }
        }

        if ($updated) {
            file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
            return response()->json(['success' => true, 'message' => 'Nota atualizada com sucesso!']);
        }

        return response()->json(['success' => false, 'message' => 'Álbum não encontrado.'], 404);
    }

    /**
     * Toggle favorite status of an album.
     */
    public function favoriteAlbum(Request $request, $id)
    {
        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return response()->json(['success' => false, 'message' => 'Cache de fotos não encontrado.'], 400);
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $updated = false;
        $isFavorite = false;

        if (isset($cacheData['albums'])) {
            foreach ($cacheData['albums'] as &$album) {
                if ($album['id'] === $id) {
                    $album['favorite'] = !($album['favorite'] ?? false);
                    $isFavorite = $album['favorite'];
                    $updated = true;
                    break;
                }
            }
        }

        if ($updated) {
            file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
            return response()->json([
                'success' => true,
                'message' => $isFavorite ? 'Álbum adicionado aos favoritos!' : 'Álbum removido dos favoritos.',
                'favorite' => $isFavorite
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Álbum não encontrado.'], 404);
    }

    /**
     * Update album tags.
     */
    public function updateTags(Request $request, $id)
    {
        $request->validate([
            'tags' => 'nullable|string',
        ]);

        $tagsString = $request->input('tags', '');
        $tagsArray = [];
        if ($tagsString) {
            $tagsArray = array_filter(array_map('trim', explode(',', $tagsString)));
        }
        $tagsArray = array_values(array_unique($tagsArray));

        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return response()->json(['success' => false, 'message' => 'Cache de fotos não encontrado.'], 400);
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $updated = false;

        if (isset($cacheData['albums'])) {
            foreach ($cacheData['albums'] as &$album) {
                if ($album['id'] === $id) {
                    $album['tags'] = $tagsArray;
                    $updated = true;
                    break;
                }
            }
        }

        if ($updated) {
            file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
            return response()->json(['success' => true, 'message' => 'Tags atualizadas!', 'tags' => $tagsArray]);
        }

        return response()->json(['success' => false, 'message' => 'Álbum não encontrado.'], 404);
    }

    /**
     * Delete album permanently from disk and cache.
     */
    public function deleteAlbum(Request $request, $id)
    {
        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return response()->json(['success' => false, 'message' => 'Cache de fotos não encontrado.'], 400);
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];
        $albumIndex = -1;
        $albumToDelete = null;

        foreach ($albums as $index => $album) {
            if ($album['id'] === $id) {
                $albumIndex = $index;
                $albumToDelete = $album;
                break;
            }
        }

        if ($albumIndex === -1 || !$albumToDelete) {
            return response()->json(['success' => false, 'message' => 'Álbum não encontrado.'], 404);
        }

        $albumPath = realpath($albumToDelete['path']);
        if (!$albumPath) {
            // Directory is already gone from disk, just remove from cache
            array_splice($cacheData['albums'], $albumIndex, 1);
            file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
            return response()->json(['success' => true, 'message' => 'Álbum removido do cache pois não existia no disco.']);
        }

        // Security boundaries check
        $basePhotosPath = realpath(trim($cacheData['photos_path'] ?? config('photos.path'), '"\''));
        $allowedHome = realpath('C:\\Users\\SaMuB\\Documents\\financeiro\\storage\\model');

        $isInsideBase = ($basePhotosPath !== false && strpos($albumPath, $basePhotosPath) === 0);
        $isInsideHome = ($allowedHome !== false && strpos($albumPath, $allowedHome) === 0);

        if (!$isInsideBase && !$isInsideHome) {
            return response()->json(['success' => false, 'message' => 'Acesso não autorizado para excluir este diretório.'], 403);
        }

        if ($albumPath === $basePhotosPath || $albumPath === $allowedHome) {
            return response()->json(['success' => false, 'message' => 'Não é permitido excluir o diretório raiz das fotos.'], 403);
        }

        // Delete from disk recursively
        try {
            if (is_dir($albumPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($albumPath);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao excluir pasta do álbum ' . $albumPath . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao excluir arquivos do disco: ' . $e->getMessage()], 500);
        }

        // Remove from cache
        array_splice($cacheData['albums'], $albumIndex, 1);
        file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));

        return response()->json(['success' => true, 'message' => 'Álbum excluído permanentemente do disco e do cache!']);
    }
}
