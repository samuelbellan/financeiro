<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PhotoController extends Controller
{
    private static ?array $cachedKnownModels = null;

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
        $photosPaths = $this->getActivePhotosPaths();
        $photosPath = implode('; ', $photosPaths);

        // Extract list of unique sites, years/months, models, and photographers for filter selectors
        $sites = array_values(array_filter(array_unique(array_column($albums, 'site'))));
        sort($sites);

        $sources = [];
        foreach ($albums as $album) {
            if (!empty($album['source_path'])) {
                $sources[] = $album['source_path'];
            }
        }
        $sources = array_values(array_unique($sources));
        sort($sources);

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

        // Models and attributes lists for filter selectors
        $models = [];
        $countries = [];
        $hairColors = [];
        $breastSizes = [];
        $ethnicities = [];

        foreach ($albums as $album) {
            if (!empty($album['model']) && $album['model'] !== 'Várias') {
                $albumModels = array_map('trim', explode(',', $album['model']));
                foreach ($albumModels as $m) {
                    if (!empty($m) && !in_array($m, ['Várias', 'Outros', 'Home'])) {
                        $models[] = $m;
                    }
                }
            }

            if (!empty($album['models']) && is_array($album['models'])) {
                foreach ($album['models'] as $m) {
                    if (!empty($m['country']) && strtolower($m['country']) !== 'unknown') {
                        $countries[] = $m['country'];
                    }
                    if (!empty($m['hair'])) {
                        $hairColors[] = $m['hair'];
                    }
                    if (!empty($m['breast'])) {
                        $breastSizes[] = $m['breast'];
                    }
                    if (!empty($m['ethnicity']) && !in_array(strtolower($m['ethnicity']), ['origin unknown', 'unknown'])) {
                        $ethnicities[] = $m['ethnicity'];
                    }
                }
            } else {
                if (!empty($album['model_country']) && strtolower($album['model_country']) !== 'unknown') {
                    $countries[] = $album['model_country'];
                }
                if (!empty($album['model_hair'])) {
                    $hairColors[] = $album['model_hair'];
                }
                if (!empty($album['model_breast'])) {
                    $breastSizes[] = $album['model_breast'];
                }
                if (!empty($album['model_ethnicity']) && !in_array(strtolower($album['model_ethnicity']), ['origin unknown', 'unknown'])) {
                    $ethnicities[] = $album['model_ethnicity'];
                }
            }
        }

        $models = array_values(array_unique($models));
        sort($models);

        $countries = array_values(array_unique($countries));
        sort($countries);

        $hairColors = array_values(array_unique($hairColors));
        sort($hairColors);

        $breastSizes = array_values(array_unique($breastSizes));
        sort($breastSizes);

        $ethnicities = array_values(array_unique($ethnicities));
        sort($ethnicities);

        $ageRanges = [
            '18-21' => '18 a 21 anos',
            '22-25' => '22 a 25 anos',
            '26-30' => '26 a 30 anos',
            '31+'   => '31+ anos'
        ];

        $sortOptions = [
            'date_desc'   => 'Data: Mais Recentes',
            'date_asc'    => 'Data: Mais Antigos',
            'model_asc'   => 'Modelo: A → Z',
            'model_desc'  => 'Modelo: Z → A',
            'name_asc'    => 'Álbum: A → Z',
            'photos_desc' => 'Fotos: Mais Fotos',
            'age_asc'     => 'Idade: Mais Jovem',
            'age_desc'    => 'Idade: Mais Velho',
            'rating_desc' => 'Avaliação: Mais Votados'
        ];

        // Apply filters
        $search = $request->query('search');
        $selectedSites = $this->normalizeFilterArray($request->query('site'));
        $selectedDates = $this->normalizeFilterArray($request->query('date')); // formats: YYYY-MM
        $selectedModels = $this->normalizeFilterArray($request->query('model'));
        $selectedStatus = $request->query('status'); // optimized or pending
        $selectedSources = $this->normalizeFilterArray($request->query('source'));
        $selectedCountries = $this->normalizeFilterArray($request->query('country'));
        $selectedHairs = $this->normalizeFilterArray($request->query('hair'));
        $selectedBreasts = $this->normalizeFilterArray($request->query('breast'));
        $selectedEthnicities = $this->normalizeFilterArray($request->query('ethnicity'));
        $selectedAges = $this->normalizeFilterArray($request->query('age'));
        $selectedSort = $request->query('sort', 'date_desc');

        $filteredAlbums = array_filter($albums, function ($album) use (
            $search, $selectedSites, $selectedDates, $selectedModels, $selectedStatus, 
            $selectedSources, $selectedCountries, $selectedHairs, $selectedBreasts, 
            $selectedEthnicities, $selectedAges
        ) {
            // Filter by Site
            if (!empty($selectedSites)) {
                $albumSite = strtolower($album['site'] ?? '');
                $siteMatch = false;
                foreach ($selectedSites as $s) {
                    if (strtolower($s) === $albumSite) {
                        $siteMatch = true;
                        break;
                    }
                }
                if (!$siteMatch) {
                    return false;
                }
            }

            // Filter by Source Location
            if (!empty($selectedSources)) {
                $albumSource = $album['source_path'] ?? '';
                if (!in_array($albumSource, $selectedSources)) {
                    return false;
                }
            }

            // Filter by Date (Year-Month)
            if (!empty($selectedDates)) {
                $albumDatePrefix = ($album['year'] ?? '') . '-' . ($album['month'] ?? '');
                if (!in_array($albumDatePrefix, $selectedDates)) {
                    return false;
                }
            }

            // Filter by Model
            if (!empty($selectedModels)) {
                $albumModel = strtolower($album['model'] ?? '');
                $modelMatch = false;
                foreach ($selectedModels as $sm) {
                    $smLower = strtolower($sm);
                    if (str_contains($albumModel, $smLower)) {
                        $modelMatch = true;
                        break;
                    }
                    if (!empty($album['models']) && is_array($album['models'])) {
                        foreach ($album['models'] as $m) {
                            if (isset($m['name']) && str_contains(strtolower($m['name']), $smLower)) {
                                $modelMatch = true;
                                break 2;
                            }
                        }
                    }
                }
                if (!$modelMatch) {
                    return false;
                }
            }

            // Filter by Country
            if (!empty($selectedCountries)) {
                $matchesCountry = false;
                foreach ($selectedCountries as $sc) {
                    if (!empty($album['models']) && is_array($album['models'])) {
                        foreach ($album['models'] as $m) {
                            if (isset($m['country']) && strcasecmp($m['country'], $sc) === 0) {
                                $matchesCountry = true;
                                break 2;
                            }
                        }
                    }
                    if (!empty($album['model_country']) && strcasecmp($album['model_country'], $sc) === 0) {
                        $matchesCountry = true;
                        break;
                    }
                }
                if (!$matchesCountry) {
                    return false;
                }
            }

            // Filter by Hair Color
            if (!empty($selectedHairs)) {
                $matchesHair = false;
                foreach ($selectedHairs as $sh) {
                    if (!empty($album['models']) && is_array($album['models'])) {
                        foreach ($album['models'] as $m) {
                            if (isset($m['hair']) && strcasecmp($m['hair'], $sh) === 0) {
                                $matchesHair = true;
                                break 2;
                            }
                        }
                    }
                    if (!empty($album['model_hair']) && strcasecmp($album['model_hair'], $sh) === 0) {
                        $matchesHair = true;
                        break;
                    }
                }
                if (!$matchesHair) {
                    return false;
                }
            }

            // Filter by Breast Size
            if (!empty($selectedBreasts)) {
                $matchesBreast = false;
                foreach ($selectedBreasts as $sb) {
                    if (!empty($album['models']) && is_array($album['models'])) {
                        foreach ($album['models'] as $m) {
                            if (isset($m['breast']) && strcasecmp($m['breast'], $sb) === 0) {
                                $matchesBreast = true;
                                break 2;
                            }
                        }
                    }
                    if (!empty($album['model_breast']) && strcasecmp($album['model_breast'], $sb) === 0) {
                        $matchesBreast = true;
                        break;
                    }
                }
                if (!$matchesBreast) {
                    return false;
                }
            }

            // Filter by Ethnicity
            if (!empty($selectedEthnicities)) {
                $matchesEth = false;
                foreach ($selectedEthnicities as $se) {
                    if (!empty($album['models']) && is_array($album['models'])) {
                        foreach ($album['models'] as $m) {
                            if (isset($m['ethnicity']) && strcasecmp($m['ethnicity'], $se) === 0) {
                                $matchesEth = true;
                                break 2;
                            }
                        }
                    }
                    if (!empty($album['model_ethnicity']) && strcasecmp($album['model_ethnicity'], $se) === 0) {
                        $matchesEth = true;
                        break;
                    }
                }
                if (!$matchesEth) {
                    return false;
                }
            }

            // Filter by Age Range
            if (!empty($selectedAges)) {
                $age = $album['model_age'] ?? null;
                if (!$age && !empty($album['models']) && is_array($album['models'])) {
                    $age = $album['models'][0]['age'] ?? null;
                }
                if (!$age) {
                    return false;
                }
                $matchesAge = false;
                foreach ($selectedAges as $sa) {
                    if ($sa === '18-21' && $age >= 18 && $age <= 21) { $matchesAge = true; break; }
                    if ($sa === '22-25' && $age >= 22 && $age <= 25) { $matchesAge = true; break; }
                    if ($sa === '26-30' && $age >= 26 && $age <= 30) { $matchesAge = true; break; }
                    if ($sa === '31+' && $age >= 31) { $matchesAge = true; break; }
                    if (is_numeric($sa) && (int)$sa === (int)$age) { $matchesAge = true; break; }
                }
                if (!$matchesAge) {
                    return false;
                }
            }

            // Filter by Optimization Status
            if ($selectedStatus === 'optimized' && empty($album['optimized'])) {
                return false;
            }
            if ($selectedStatus === 'pending' && !empty($album['optimized'])) {
                return false;
            }

            // Filter by Search Query (Combined filters with multi-token, quotes, prefixes, exclusions and accents)
            if ($search !== null && trim($search) !== '') {
                if (!$this->matchesCombinedSearch($album, $search)) {
                    return false;
                }
            }

            return true;
        });

        // Sorting
        usort($filteredAlbums, function ($a, $b) use ($selectedSort) {
            switch ($selectedSort) {
                case 'date_asc':
                    return strcmp($a['date'] ?? '', $b['date'] ?? '');
                case 'model_asc':
                    return strcasecmp($a['model'] ?? 'zzz', $b['model'] ?? 'zzz');
                case 'model_desc':
                    return strcasecmp($b['model'] ?? '', $a['model'] ?? '');
                case 'name_asc':
                    return strcasecmp($a['album_name'] ?? '', $b['album_name'] ?? '');
                case 'photos_desc':
                    return ($b['file_count'] ?? 0) <=> ($a['file_count'] ?? 0);
                case 'age_asc':
                    $ageA = $a['model_age'] ?? 999;
                    $ageB = $b['model_age'] ?? 999;
                    return $ageA <=> $ageB;
                case 'age_desc':
                    $ageA = $a['model_age'] ?? 0;
                    $ageB = $b['model_age'] ?? 0;
                    return $ageB <=> $ageA;
                case 'rating_desc':
                    return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
                case 'date_desc':
                default:
                    return strcmp($b['date'] ?? '', $a['date'] ?? '');
            }
        });

        // Active Filter Chips for transparent combined filtering UI
        $activeFilterChips = [];
        $allQueryParams = $request->query();

        if ($search !== null && trim($search) !== '') {
            $p = $allQueryParams;
            unset($p['search']);
            $activeFilterChips[] = [
                'type' => 'search',
                'label' => 'Busca',
                'value' => '"' . $search . '"',
                'url' => route('photos.index', $p),
                'icon' => 'search'
            ];
        }

        if (!empty($selectedModels)) {
            foreach ($selectedModels as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedModels, [$val]));
                if (empty($rem)) unset($p['model']); else $p['model'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $activeFilterChips[] = [
                    'type' => 'model',
                    'label' => 'Modelo',
                    'value' => $val,
                    'url' => route('photos.index', $p),
                    'icon' => 'user'
                ];
            }
        }

        if (!empty($selectedSites)) {
            foreach ($selectedSites as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedSites, [$val]));
                if (empty($rem)) unset($p['site']); else $p['site'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $activeFilterChips[] = [
                    'type' => 'site',
                    'label' => 'Produtor',
                    'value' => $val,
                    'url' => route('photos.index', $p),
                    'icon' => 'globe'
                ];
            }
        }

        if (!empty($selectedCountries)) {
            foreach ($selectedCountries as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedCountries, [$val]));
                if (empty($rem)) unset($p['country']); else $p['country'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $flag = self::getCountryFlag($val);
                $activeFilterChips[] = [
                    'type' => 'country',
                    'label' => 'País',
                    'value' => ($flag ? $flag . ' ' : '') . $val,
                    'url' => route('photos.index', $p),
                    'icon' => 'flag'
                ];
            }
        }

        if (!empty($selectedHairs)) {
            foreach ($selectedHairs as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedHairs, [$val]));
                if (empty($rem)) unset($p['hair']); else $p['hair'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $activeFilterChips[] = [
                    'type' => 'hair',
                    'label' => 'Cabelo',
                    'value' => $val,
                    'url' => route('photos.index', $p),
                    'icon' => 'sparkles'
                ];
            }
        }

        if (!empty($selectedBreasts)) {
            foreach ($selectedBreasts as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedBreasts, [$val]));
                if (empty($rem)) unset($p['breast']); else $p['breast'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $activeFilterChips[] = [
                    'type' => 'breast',
                    'label' => 'Seios',
                    'value' => $val,
                    'url' => route('photos.index', $p),
                    'icon' => 'heart'
                ];
            }
        }

        if (!empty($selectedEthnicities)) {
            foreach ($selectedEthnicities as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedEthnicities, [$val]));
                if (empty($rem)) unset($p['ethnicity']); else $p['ethnicity'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $activeFilterChips[] = [
                    'type' => 'ethnicity',
                    'label' => 'Etnia',
                    'value' => $val,
                    'url' => route('photos.index', $p),
                    'icon' => 'users'
                ];
            }
        }

        if (!empty($selectedAges)) {
            foreach ($selectedAges as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedAges, [$val]));
                if (empty($rem)) unset($p['age']); else $p['age'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $label = $ageRanges[$val] ?? ($val . ' anos');
                $activeFilterChips[] = [
                    'type' => 'age',
                    'label' => 'Idade',
                    'value' => $label,
                    'url' => route('photos.index', $p),
                    'icon' => 'calendar'
                ];
            }
        }

        if (!empty($selectedStatus)) {
            $p = $allQueryParams;
            unset($p['status']);
            $activeFilterChips[] = [
                'type' => 'status',
                'label' => 'Miniaturas',
                'value' => $selectedStatus === 'optimized' ? '⚡ Com Miniaturas' : '⏳ Pendentes',
                'url' => route('photos.index', $p),
                'icon' => 'zap'
            ];
        }

        if (!empty($selectedDates)) {
            foreach ($selectedDates as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedDates, [$val]));
                if (empty($rem)) unset($p['date']); else $p['date'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $label = $yearsMonths[$val]['label'] ?? $val;
                $activeFilterChips[] = [
                    'type' => 'date',
                    'label' => 'Período',
                    'value' => $label,
                    'url' => route('photos.index', $p),
                    'icon' => 'calendar'
                ];
            }
        }

        if (!empty($selectedSources)) {
            foreach ($selectedSources as $val) {
                $p = $allQueryParams;
                $rem = array_values(array_diff($selectedSources, [$val]));
                if (empty($rem)) unset($p['source']); else $p['source'] = count($rem) === 1 ? $rem[0] : implode(',', $rem);
                $activeFilterChips[] = [
                    'type' => 'source',
                    'label' => 'Local',
                    'value' => basename($val) ?: $val,
                    'url' => route('photos.index', $p),
                    'icon' => 'folder'
                ];
            }
        }

        return view('photos.index', [
            'albums' => $filteredAlbums,
            'sites' => $sites,
            'sources' => $sources,
            'yearsMonths' => $yearsMonths,
            'models' => $models,
            'countries' => $countries,
            'hairColors' => $hairColors,
            'breastSizes' => $breastSizes,
            'ethnicities' => $ethnicities,
            'ageRanges' => $ageRanges,
            'sortOptions' => $sortOptions,
            'lastSync' => $lastSync,
            'photosPaths' => $photosPaths,
            'photosPath' => $photosPath,
            'allAlbumsCount' => count($albums),
            'optimizedCount' => count(array_filter($albums, fn($a) => !empty($a['optimized']))),
            'pendingCount' => count(array_filter($albums, fn($a) => empty($a['optimized']))),
            'activeFilterChips' => $activeFilterChips,
            'selectedModels' => $selectedModels,
            'selectedCountries' => $selectedCountries,
            'selectedHairs' => $selectedHairs,
            'selectedBreasts' => $selectedBreasts,
            'selectedEthnicities' => $selectedEthnicities,
            'selectedAges' => $selectedAges,
            'selectedSites' => $selectedSites,
            'selectedDates' => $selectedDates,
            'selectedSources' => $selectedSources,
            'filters' => [
                'search' => $search,
                'site' => $request->query('site'),
                'date' => $request->query('date'),
                'model' => $request->query('model'),
                'status' => $selectedStatus,
                'source' => $request->query('source'),
                'country' => $request->query('country'),
                'hair' => $request->query('hair'),
                'breast' => $request->query('breast'),
                'ethnicity' => $request->query('ethnicity'),
                'age' => $request->query('age'),
                'sort' => $selectedSort,
            ]
        ]);
    }

    /**
     * Normalize a query filter value into an array of trimmed non-empty strings.
     */
    public function normalizeFilterArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn($v) => $v !== ''));
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return [];
            }
            if (str_contains($trimmed, ',')) {
                return array_values(array_filter(array_map('trim', explode(',', $trimmed)), fn($v) => $v !== ''));
            }
            return [$trimmed];
        }
        return [(string)$value];
    }

    /**
     * Build aggregated searchable full text for an album.
     */
    public function buildAlbumSearchableText(array $album): string
    {
        $parts = [];
        $parts[] = $album['album_name'] ?? '';
        $parts[] = $album['folder_name'] ?? '';
        $parts[] = $album['site'] ?? '';
        $parts[] = $album['model'] ?? '';
        $parts[] = $album['photographer'] ?? '';
        $parts[] = $album['date'] ?? '';
        $parts[] = $album['year'] ?? '';
        $parts[] = $album['month'] ?? '';
        $parts[] = $album['model_country'] ?? '';
        $parts[] = $album['model_hair'] ?? '';
        $parts[] = $album['model_eye'] ?? '';
        $parts[] = $album['model_breast'] ?? '';
        $parts[] = $album['model_ethnicity'] ?? '';
        if (!empty($album['model_age'])) {
            $parts[] = (string)$album['model_age'];
            $parts[] = $album['model_age'] . ' anos';
        }
        $parts[] = $album['model_bio'] ?? '';
        if (!empty($album['tags']) && is_array($album['tags'])) {
            $parts[] = implode(' ', $album['tags']);
        }
        if (!empty($album['models']) && is_array($album['models'])) {
            foreach ($album['models'] as $m) {
                $parts[] = $m['name'] ?? '';
                $parts[] = $m['country'] ?? '';
                $parts[] = $m['hair'] ?? '';
                $parts[] = $m['eye'] ?? '';
                $parts[] = $m['breast'] ?? '';
                $parts[] = $m['ethnicity'] ?? '';
                if (!empty($m['age'])) {
                    $parts[] = (string)$m['age'];
                }
                $parts[] = $m['bio'] ?? '';
            }
        }
        if (!empty($album['source_path'])) {
            $parts[] = basename($album['source_path']);
        }

        $raw = implode(' ', array_filter($parts));
        $lower = mb_strtolower($raw, 'UTF-8');
        $ascii = strtolower(\Illuminate\Support\Str::ascii($lower));
        return $lower . ' ' . $ascii;
    }

    /**
     * Check if an album matches a search query with combined terms, quotes, prefixes, and exclusions.
     */
    public function matchesCombinedSearch(array $album, string $search): bool
    {
        $search = trim($search);
        if ($search === '') {
            return true;
        }

        preg_match_all('/(?:([a-zA-Z0-9_\-]+):)?"([^"]+)"|(?:([a-zA-Z0-9_\-]+):)?([^\s,]+)/u', $search, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return true;
        }

        $albumFullText = $this->buildAlbumSearchableText($album);

        foreach ($matches as $match) {
            $prefix = !empty($match[1]) ? $match[1] : (!empty($match[3]) ? $match[3] : null);
            $token = !empty($match[2]) ? $match[2] : (!empty($match[4]) ? $match[4] : '');
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $isNegation = false;
            if ($prefix && str_starts_with($prefix, '-')) {
                $isNegation = true;
                $prefix = substr($prefix, 1);
            } elseif (str_starts_with($token, '-') && strlen($token) > 1) {
                $isNegation = true;
                $token = substr($token, 1);
            }

            $tokenLower = mb_strtolower($token, 'UTF-8');
            $tokenAscii = strtolower(\Illuminate\Support\Str::ascii($tokenLower));

            if ($prefix) {
                $matched = $this->matchesPrefix($album, strtolower($prefix), $tokenLower, $tokenAscii);
            } else {
                $matched = (str_contains($albumFullText, $tokenLower) || str_contains($albumFullText, $tokenAscii));
            }

            if ($isNegation) {
                if ($matched) {
                    return false;
                }
            } else {
                if (!$matched) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check prefix-specific match for search attributes.
     */
    protected function matchesPrefix(array $album, string $prefix, string $tokenLower, string $tokenAscii): bool
    {
        switch ($prefix) {
            case 'site':
            case 'produtor':
            case 'producer':
            case 'studio':
            case 'canal':
                $site = strtolower($album['site'] ?? '');
                return str_contains($site, $tokenLower) || str_contains($site, $tokenAscii);

            case 'model':
            case 'modelo':
            case 'girl':
            case 'atriz':
                $model = mb_strtolower($album['model'] ?? '', 'UTF-8');
                if (str_contains($model, $tokenLower) || str_contains(\Illuminate\Support\Str::ascii($model), $tokenAscii)) {
                    return true;
                }
                if (!empty($album['models']) && is_array($album['models'])) {
                    foreach ($album['models'] as $m) {
                        $mName = mb_strtolower($m['name'] ?? '', 'UTF-8');
                        if (str_contains($mName, $tokenLower) || str_contains(\Illuminate\Support\Str::ascii($mName), $tokenAscii)) {
                            return true;
                        }
                    }
                }
                return false;

            case 'country':
            case 'pais':
            case 'país':
            case 'origem':
            case 'nacionalidade':
                $countries = [];
                if (!empty($album['model_country'])) {
                    $countries[] = $album['model_country'];
                }
                if (!empty($album['models']) && is_array($album['models'])) {
                    foreach ($album['models'] as $m) {
                        if (!empty($m['country'])) $countries[] = $m['country'];
                    }
                }
                foreach ($countries as $c) {
                    $cLower = mb_strtolower($c, 'UTF-8');
                    $cAscii = strtolower(\Illuminate\Support\Str::ascii($cLower));
                    if (str_contains($cLower, $tokenLower) || str_contains($cAscii, $tokenAscii) || str_contains($cLower, $tokenAscii)) {
                        return true;
                    }
                }
                return false;

            case 'hair':
            case 'cabelo':
            case 'cor_cabelo':
                $hairs = [];
                if (!empty($album['model_hair'])) $hairs[] = $album['model_hair'];
                if (!empty($album['models']) && is_array($album['models'])) {
                    foreach ($album['models'] as $m) {
                        if (!empty($m['hair'])) $hairs[] = $m['hair'];
                    }
                }
                foreach ($hairs as $h) {
                    $hLower = mb_strtolower($h, 'UTF-8');
                    $hAscii = strtolower(\Illuminate\Support\Str::ascii($hLower));
                    if (str_contains($hLower, $tokenLower) || str_contains($hAscii, $tokenAscii)) {
                        return true;
                    }
                }
                return false;

            case 'breast':
            case 'seio':
            case 'seios':
                $breasts = [];
                if (!empty($album['model_breast'])) $breasts[] = $album['model_breast'];
                if (!empty($album['models']) && is_array($album['models'])) {
                    foreach ($album['models'] as $m) {
                        if (!empty($m['breast'])) $breasts[] = $m['breast'];
                    }
                }
                foreach ($breasts as $b) {
                    $bLower = mb_strtolower($b, 'UTF-8');
                    $bAscii = strtolower(\Illuminate\Support\Str::ascii($bLower));
                    if (str_contains($bLower, $tokenLower) || str_contains($bAscii, $tokenAscii)) {
                        return true;
                    }
                }
                return false;

            case 'ethnicity':
            case 'etnia':
                $ethnicities = [];
                if (!empty($album['model_ethnicity'])) $ethnicities[] = $album['model_ethnicity'];
                if (!empty($album['models']) && is_array($album['models'])) {
                    foreach ($album['models'] as $m) {
                        if (!empty($m['ethnicity'])) $ethnicities[] = $m['ethnicity'];
                    }
                }
                foreach ($ethnicities as $eth) {
                    $ethLower = mb_strtolower($eth, 'UTF-8');
                    $ethAscii = strtolower(\Illuminate\Support\Str::ascii($ethLower));
                    if (str_contains($ethLower, $tokenLower) || str_contains($ethAscii, $tokenAscii)) {
                        return true;
                    }
                }
                return false;

            case 'age':
            case 'idade':
                $age = $album['model_age'] ?? null;
                if (!$age && !empty($album['models']) && is_array($album['models'])) {
                    $age = $album['models'][0]['age'] ?? null;
                }
                if (!$age) return false;
                if ($tokenLower === '18-21' && $age >= 18 && $age <= 21) return true;
                if ($tokenLower === '22-25' && $age >= 22 && $age <= 25) return true;
                if ($tokenLower === '26-30' && $age >= 26 && $age <= 30) return true;
                if (($tokenLower === '31+' || $tokenLower === '31') && $age >= 31) return true;
                return (string)$age === $tokenLower;

            case 'year':
            case 'ano':
                $year = (string)($album['year'] ?? '');
                return str_contains($year, $tokenLower);

            case 'month':
            case 'mes':
            case 'mês':
                $month = (string)($album['month'] ?? '');
                return str_contains($month, $tokenLower);

            case 'date':
            case 'data':
            case 'periodo':
            case 'período':
                $date = (string)($album['date'] ?? '');
                return str_contains($date, $tokenLower);

            case 'album':
            case 'name':
            case 'nome':
            case 'ensaio':
                $name = mb_strtolower($album['album_name'] ?? '', 'UTF-8');
                $nameAscii = strtolower(\Illuminate\Support\Str::ascii($name));
                return str_contains($name, $tokenLower) || str_contains($nameAscii, $tokenAscii);

            case 'tag':
            case 'tags':
                if (!empty($album['tags']) && is_array($album['tags'])) {
                    foreach ($album['tags'] as $t) {
                        $tLower = mb_strtolower($t, 'UTF-8');
                        if (str_contains($tLower, $tokenLower)) {
                            return true;
                        }
                    }
                }
                return false;

            case 'status':
                if (in_array($tokenLower, ['otimizado', 'otimizada', 'optimized', 'thumb', 'miniaturas', 'sim', 'yes'])) {
                    return !empty($album['optimized']);
                }
                if (in_array($tokenLower, ['pendente', 'pending', 'nao', 'não', 'no'])) {
                    return empty($album['optimized']);
                }
                return false;

            case 'source':
            case 'local':
            case 'pasta':
                $source = strtolower($album['source_path'] ?? '');
                $folder = strtolower($album['folder_name'] ?? '');
                return str_contains($source, $tokenLower) || str_contains($folder, $tokenLower);

            case 'photographer':
            case 'fotografo':
            case 'fotógrafo':
                $photo = mb_strtolower($album['photographer'] ?? '', 'UTF-8');
                return str_contains($photo, $tokenLower) || str_contains(\Illuminate\Support\Str::ascii($photo), $tokenAscii);

            default:
                $fullText = $this->buildAlbumSearchableText($album);
                return str_contains($fullText, $prefix) && (str_contains($fullText, $tokenLower) || str_contains($fullText, $tokenAscii));
        }
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
     * Get all currently configured photo directory paths.
     */
    public function getActivePhotosPaths(): array
    {
        $cachePath = config('photos.cache_path');
        if (file_exists($cachePath)) {
            $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
            if (isset($cacheData['photos_paths']) && is_array($cacheData['photos_paths'])) {
                return array_values(array_filter(array_map('trim', $cacheData['photos_paths'])));
            }
            if (!empty($cacheData['photos_path'])) {
                $paths = explode(';', $cacheData['photos_path']);
                return array_values(array_filter(array_map('trim', $paths)));
            }
        }

        $single = config('photos.path');
        $multiple = config('photos.paths');

        if (!empty($single) && (empty($multiple) || !in_array($single, $multiple))) {
            return array_values(array_filter(array_map('trim', explode(';', $single))));
        }

        if (!empty($multiple) && is_array($multiple)) {
            return array_values(array_filter(array_map('trim', $multiple)));
        }

        if (!empty($single)) {
            return array_values(array_filter(array_map('trim', explode(';', $single))));
        }

        return [];
    }

    /**
     * Add a new directory location to photo storage/mapping.
     */
    public function addLocation(Request $request)
    {
        $newPath = trim($request->input('path', ''), " \t\n\r\0\x0B\"'");
        if (empty($newPath)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'O caminho da pasta não pode ser vazio.'], 422);
            }
            return redirect()->route('photos.index')->with('error', 'O caminho da pasta não pode ser vazio.');
        }

        $newPath = str_replace('/', DIRECTORY_SEPARATOR, $newPath);

        $activePaths = $this->getActivePhotosPaths();
        foreach ($activePaths as $existing) {
            $existingNorm = str_replace('/', DIRECTORY_SEPARATOR, $existing);
            if (strcasecmp($existingNorm, $newPath) === 0 || (is_dir($existingNorm) && is_dir($newPath) && realpath($existingNorm) === realpath($newPath))) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Este diretório já está cadastrado.'], 422);
                }
                return redirect()->route('photos.index')->with('error', 'Este diretório já está cadastrado.');
            }
        }

        $activePaths[] = $newPath;
        try {
            $this->performSync($activePaths);
        } catch (\Throwable $e) {
            Log::error('Erro ao sincronizar após adicionar local: ' . $e->getMessage());
        }

        $msg = 'Local de fotos adicionado com sucesso!';
        if (!is_dir($newPath)) {
            $msg .= ' (Nota: o caminho não foi encontrado no disco neste momento)';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'paths' => $activePaths
            ]);
        }

        return redirect()->route('photos.index')->with('success', $msg);
    }

    /**
     * Remove a directory location from photo storage/mapping.
     */
    public function removeLocation(Request $request)
    {
        $pathToRemove = trim($request->input('path', ''), " \t\n\r\0\x0B\"'");
        $pathToRemoveNorm = str_replace('/', DIRECTORY_SEPARATOR, $pathToRemove);
        $activePaths = $this->getActivePhotosPaths();

        $found = false;
        $newPaths = [];
        foreach ($activePaths as $p) {
            $pNorm = str_replace('/', DIRECTORY_SEPARATOR, $p);
            if (strcasecmp($pNorm, $pathToRemoveNorm) === 0 || (is_dir($pNorm) && is_dir($pathToRemoveNorm) && realpath($pNorm) === realpath($pathToRemoveNorm))) {
                $found = true;
            } else {
                $newPaths[] = $p;
            }
        }

        if (!$found) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Local não encontrado na lista.'], 404);
            }
            return redirect()->route('photos.index')->with('error', 'Local não encontrado na lista.');
        }

        try {
            $this->performSync($newPaths);
        } catch (\Throwable $e) {
            Log::error('Erro ao sincronizar após remover local: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Local de fotos removido com sucesso!',
                'paths' => $newPaths
            ]);
        }

        return redirect()->route('photos.index')->with('success', 'Local de fotos removido com sucesso!');
    }

    /**
     * Sync and rebuild the photo albums cache.
     */
    public function sync(Request $request)
    {
        try {
            $customPaths = $request->input('photos_paths');
            $customPath = $request->input('photos_path');
            $this->performSync($customPaths ?: $customPath);
            return redirect()->route('photos.index')->with('success', 'Mapeamento inteligente de fotos atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar fotos: ' . $e->getMessage());
            return redirect()->route('photos.index')->with('error', 'Erro ao sincronizar fotos: ' . $e->getMessage());
        }
    }

    /**
     * Perform the actual directory scanning and write cache.
     */
    private function performSync($customPaths = null)
    {
        @set_time_limit(300);
        $cachePath = config('photos.cache_path');
        $existingAlbums = [];
        $existingPhotosPaths = null;
        if (file_exists($cachePath)) {
            $oldCache = json_decode(file_get_contents($cachePath), true);
            if (isset($oldCache['photos_paths']) && is_array($oldCache['photos_paths'])) {
                $existingPhotosPaths = $oldCache['photos_paths'];
            } elseif (!empty($oldCache['photos_path'])) {
                $existingPhotosPaths = explode(';', $oldCache['photos_path']);
            }

            if (isset($oldCache['albums'])) {
                foreach ($oldCache['albums'] as $oldAlbum) {
                    $existingAlbums[$oldAlbum['path']] = $oldAlbum;
                }
            }
        }

        if ($customPaths !== null) {
            if (is_array($customPaths)) {
                $targetPaths = $customPaths;
            } else {
                $targetPaths = explode(';', $customPaths);
            }
        } elseif (!empty($existingPhotosPaths)) {
            $targetPaths = $existingPhotosPaths;
        } else {
            $targetPaths = $this->getActivePhotosPaths();
        }

        $basePaths = [];
        foreach ($targetPaths as $tp) {
            $trimmed = trim($tp, " \t\n\r\0\x0B\"'");
            if ($trimmed !== '') {
                $basePaths[] = str_replace('/', DIRECTORY_SEPARATOR, $trimmed);
            }
        }
        $basePaths = array_values(array_unique($basePaths));
        
        $albums = [];
        $scannedAlbumPaths = [];

        $knownModels = [];
        foreach ($existingAlbums as $ea) {
            if (!empty($ea['model'])) {
                foreach (explode(',', $ea['model']) as $p) {
                    $trimP = trim($p);
                    if (strlen($trimP) > 2) {
                        $knownModels[strtolower($trimP)] = $trimP;
                    }
                }
            }
        }

        // 1. Scan each configured $basePath
        foreach ($basePaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $realBasePath = realpath($basePath) ?: $basePath;

            // Check if $realBasePath itself contains images directly (is an album itself)
            $rootImages = $this->safeGlobImages($realBasePath);
            if (!empty($rootImages) && !isset($scannedAlbumPaths[$realBasePath])) {
                $folderName = basename($realBasePath);
                $coverImage = null;
                foreach ($rootImages as $img) {
                    if (stripos(basename($img), 'cover') !== false) {
                        $coverImage = $img;
                        break;
                    }
                }
                if (!$coverImage) {
                    $coverImage = $rootImages[0];
                }

                $albumData = $this->parseAlbumFolder($realBasePath, $folderName, count($rootImages), $coverImage, $knownModels);
                $albumData['source_path'] = $realBasePath;
                if (!empty($albumData['model'])) {
                    foreach (explode(',', $albumData['model']) as $p) {
                        $trimP = trim($p);
                        if (strlen($trimP) > 2) {
                            $knownModels[strtolower($trimP)] = $trimP;
                        }
                    }
                }

                if (isset($existingAlbums[$realBasePath])) {
                    $oldAlbum = $existingAlbums[$realBasePath];
                    if ($oldAlbum['file_count'] === count($rootImages) && !empty($oldAlbum['optimized'])) {
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

                $albums[] = $albumData;
                $scannedAlbumPaths[$realBasePath] = true;
            }

            try {
                $dir = new \RecursiveDirectoryIterator($realBasePath, \FilesystemIterator::SKIP_DOTS);
                $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD);
                $realBasePathNorm = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realBasePath), DIRECTORY_SEPARATOR);

                foreach ($iterator as $fileInfo) {
                    try {
                        if ($fileInfo->isDir()) {
                            $path = $fileInfo->getRealPath();
                            if (!$path) continue;
                            
                            $pathNorm = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
                            $depth = 1;
                            if (stripos($pathNorm, $realBasePathNorm) === 0) {
                                $relPath = substr($pathNorm, strlen($realBasePathNorm));
                                $depth = substr_count(trim($relPath, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR) + 1;
                            }
                            
                            if ($depth > 5) {
                                continue;
                            }

                            // Check for images safely (supports square brackets)
                            $images = $this->safeGlobImages($path);
                            if (!empty($images) && !isset($scannedAlbumPaths[$path])) {
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
                                
                                $albumData = $this->parseAlbumFolder($path, $folderName, count($images), $coverImage, $knownModels);
                                $albumData['source_path'] = $realBasePath;
                                if (!empty($albumData['model'])) {
                                    foreach (explode(',', $albumData['model']) as $p) {
                                        $trimP = trim($p);
                                        if (strlen($trimP) > 2) {
                                            $knownModels[strtolower($trimP)] = $trimP;
                                        }
                                    }
                                }
                                
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

                                $albums[] = $albumData;
                                $scannedAlbumPaths[$path] = true;
                            }
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Erro ao escanear diretório {$realBasePath}: " . $e->getMessage());
            }
        }

        // 2. Add C:\Users\SaMuB\Documents\financeiro\storage\model if it exists and not already scanned
        $localSample = 'C:\\Users\\SaMuB\\Documents\\financeiro\\storage\\model';
        if (is_dir($localSample) && !isset($scannedAlbumPaths[realpath($localSample)])) {
            $images = $this->safeGlobImages($localSample);
            if (!empty($images)) {
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
                    'tags' => $tags,
                    'source_path' => $localSample
                ];
            }
        }

        // Write cache
        $cacheData = [
            'last_sync' => date('Y-m-d H:i:s'),
            'photos_paths' => $basePaths,
            'photos_path' => implode('; ', $basePaths),
            'albums' => $albums
        ];

        $cacheDir = dirname(config('photos.cache_path'));
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents(config('photos.cache_path'), json_encode($cacheData, JSON_PRETTY_PRINT));
    }

    /**
     * Parse any model biographical text files (*.txt) inside an album directory.
     */
    public function parseModelBioFiles($albumPath): array
    {
        if (!is_dir($albumPath)) {
            return [];
        }

        $txtFiles = glob($albumPath . DIRECTORY_SEPARATOR . '*.txt');
        if (empty($txtFiles)) {
            return [];
        }

        $models = [];
        foreach ($txtFiles as $txtFile) {
            $fileName = basename($txtFile);
            $content = @file_get_contents($txtFile);
            if ($content === false || trim($content) === '') {
                continue;
            }

            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252');
            }
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // Strip BOM

            $lines = array_values(array_filter(array_map('trim', explode("\n", $content))));

            // Determine model name
            $name = '';
            if (preg_match('/^model-bio_(.+)\.txt$/i', $fileName, $m)) {
                $name = str_replace('-', ' ', $m[1]);
            } elseif (!empty($lines) && !str_contains($lines[0], ':')) {
                $name = $lines[0];
            }

            $getVal = function ($pattern) use ($content) {
                if (preg_match($pattern, $content, $match)) {
                    $val = trim($match[1]);
                    return $val !== '' ? $val : null;
                }
                return null;
            };

            $ageRaw = $getVal('/Age(?:\s+when\s+shot)?:\s*(\d+)/i');
            $age = $ageRaw ? (int)$ageRaw : null;
            $country = $getVal('/Country:\s*([^\r\n]+)/i');
            $hair = $getVal('/Hair(?:\s*color)?:\s*([^\r\n]+)/i');
            $eye = $getVal('/Eye(?:\s*color)?:\s*([^\r\n]+)/i');
            $height = $getVal('/Height:\s*([^\r\n]+)/i');
            $weight = $getVal('/Weight:\s*([^\r\n]+)/i');
            $breast = $getVal('/Breast(?:\s*size)?:\s*([^\r\n]+)/i');
            $measurements = $getVal('/Measurements:\s*([^\r\n]+)/i');
            $ethnicity = $getVal('/Ethnicity:\s*([^\r\n]+)/i');

            $bio = null;
            if (preg_match('/Bio:\s*([\s\S]+)$/i', $content, $bMatch)) {
                $bio = trim($bMatch[1]);
            }

            // Normalization
            if ($name) {
                $name = ucwords(strtolower($name));
            }
            if ($country) {
                $country = ucwords(strtolower($country));
            }
            if ($hair) {
                $hair = ucfirst(strtolower($hair));
            }
            if ($eye) {
                $eye = ucfirst(strtolower($eye));
            }
            if ($breast) {
                $breast = ucfirst(strtolower($breast));
            }
            if ($ethnicity) {
                $ethnicity = ucwords(strtolower($ethnicity));
            }

            $models[] = [
                'name' => $name ?: 'Modelo',
                'age' => $age,
                'country' => $country,
                'hair' => $hair,
                'eye' => $eye,
                'height' => $height,
                'weight' => $weight,
                'breast' => $breast,
                'measurements' => $measurements,
                'ethnicity' => $ethnicity,
                'bio' => $bio,
                'source_file' => $fileName
            ];
        }

        return $models;
    }

    /**
     * Return country flag emoji or icon for display.
     */
    public static function getCountryFlag($country): string
    {
        if (empty($country) || strtolower($country) === 'unknown') {
            return '';
        }
        $map = [
            'united states' => '🇺🇸',
            'usa' => '🇺🇸',
            'russian federation' => '🇷🇺',
            'russia' => '🇷🇺',
            'ukraine' => '🇺🇦',
            'spain' => '🇪🇸',
            'latvia' => '🇱🇻',
            'slovenia' => '🇸🇮',
            'belarus' => '🇧🇾',
            'belgium' => '🇧🇪',
            'estonia' => '🇪🇪',
            'austria' => '🇦🇹',
            'italy' => '🇮🇹',
            'france' => '🇫🇷',
            'venezuela' => '🇻🇪',
            'hungary' => '🇭🇺',
            'netherlands' => '🇳🇱',
            'colombia' => '🇨🇴',
            'thailand' => '🇹🇭',
            'norway' => '🇳🇴',
            'czech republic' => '🇨🇿',
            'poland' => '🇵🇱',
            'japan' => '🇯🇵',
            'philippines' => '🇵🇭',
            'kazakhstan' => '🇰🇿',
            'israel' => '🇮🇱',
            'finland' => '🇫🇮',
            'romania' => '🇷🇴',
            'georgia' => '🇬🇪',
            'canada' => '🇨🇦',
            'switzerland' => '🇨🇭',
            'united kingdom' => '🇬🇧',
            'uk' => '🇬🇧',
            'germany' => '🇩🇪',
            'portugal' => '🇵🇹',
            'vietnam' => '🇻🇳',
            'macedonia' => '🇲🇰',
            'lithuania' => '🇱🇹',
            'moldova, republic of' => '🇲🇩',
            'moldova' => '🇲🇩',
            'indonesia' => '🇮🇩',
            'argentina' => '🇦🇷',
            'montenegro' => '🇲🇪',
            'bulgaria' => '🇧🇬',
            'tajikistan' => '🇹🇯',
            'slovakia' => '🇸🇰',
            'panama' => '🇵🇦',
            'sweden' => '🇸🇪',
            'ecuador' => '🇪🇨',
            'brazil' => '🇧🇷',
            'brasil' => '🇧🇷',
        ];
        $lower = strtolower(trim($country));
        return $map[$lower] ?? '🌐';
    }

    /**
     * Parse folder details intelligently.
     */
    private function parseAlbumFolder($path, $folderName, $fileCount, $coverImage, array $knownModels = [])
    {
        $id = md5($path);
        
        $site = 'Outros';
        $date = null;
        $model = null;
        $albumName = $folderName;
        $photographer = null;

        if (empty($knownModels)) {
            if (self::$cachedKnownModels === null) {
                self::$cachedKnownModels = [];
                $cacheFile = config('photos.cache_path');
                if (file_exists($cacheFile)) {
                    $cData = json_decode(file_get_contents($cacheFile), true) ?? [];
                    foreach ($cData['albums'] ?? [] as $ca) {
                        if (!empty($ca['model'])) {
                            foreach (explode(',', $ca['model']) as $cp) {
                                $cTrim = trim($cp);
                                if (strlen($cTrim) > 2) {
                                    self::$cachedKnownModels[strtolower($cTrim)] = $cTrim;
                                }
                            }
                        }
                    }
                }
            }
            $knownModels = self::$cachedKnownModels ?? [];
        }

        // Clean folder name to remove special symbols/tags for matching
        $cleanFolderName = trim($folderName);
        $cleanBase = preg_replace('/(_[a-f0-9]{4,10})?_(med|mid|low|high|large|small|huge)$/i', '', $cleanFolderName);
        $cleanBase = preg_replace('/_[a-f0-9]{4,10}$/i', '', $cleanBase);

        // Scan for model bio text files in directory
        $parsedModels = $this->parseModelBioFiles($path);
        if (!empty($parsedModels)) {
            $modelNames = array_unique(array_filter(array_column($parsedModels, 'name')));
            if (!empty($modelNames)) {
                $model = implode(', ', $modelNames);
            }
        }

        // Pattern 1 (Watch4Beauty Style / Dotted): Watch4Beauty_26.09.05.Irene.Rouse.My.Sexy.Legs_med
        if (preg_match('/^([a-z0-9]+)_(\d{2}\.\d{2}\.\d{2})[\._](.+)$/i', $cleanBase, $matches)) {
            $site = $matches[1];
            $date = '20' . str_replace('.', '-', $matches[2]);
            $dotted = $matches[3];
            $parts = preg_split('/[\._]/', $dotted);
            $parts = array_values(array_filter(array_map('trim', $parts)));

            if ($model) {
                $escapedModel = preg_quote($model, '/');
                $candAlbum = preg_replace('/' . preg_replace('/\s+/', '[\._\\s]+', $escapedModel) . '/i', '', implode(' ', $parts));
                $candAlbum = trim(preg_replace('/\s+/', ' ', $candAlbum));
                $albumName = !empty($candAlbum) ? $candAlbum : implode(' ', $parts);
            } else {
                if (count($parts) >= 3) {
                    $model = $parts[0] . ' ' . $parts[1];
                    $albumName = implode(' ', array_slice($parts, 2));
                } elseif (count($parts) === 2) {
                    $model = $parts[0];
                    $albumName = $parts[1];
                } else {
                    $albumName = implode(' ', $parts);
                }
            }
        }
        // Pattern 2 (Femjoy with 2 underscores before by-): Femjoy_2026-09-01_LIGHT-BLUE_LUNA-MOONIE_by-TOM-LEONARD_large
        elseif (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_([^_]+)_([^_]+)_by-(.+)$/i', $cleanBase, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            $albumName = str_replace(['-', '_'], ' ', $matches[3]);
            if (!$model) {
                $model = str_replace(['-', '_'], ' ', $matches[4]);
            }
            $photographer = str_replace(['-', '_'], ' ', $matches[5]);
        }
        // Pattern 3 (Stunning Style with ---): Stunning_2026-01-30_ADRIENNE---GET-NAKED-ON-THE-COUCH-ADRIENNE-W-by-THIERRY-MURRELL_d5640_med
        elseif (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_([a-z0-9\-]+)---(.+?)[-_]by-(.+)$/i', $cleanBase, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            $candModel = str_replace(['-', '_'], ' ', $matches[3]);
            $rawAlbum = $matches[4];
            $photographer = str_replace(['-', '_'], ' ', $matches[5]);

            if (preg_match('/' . preg_quote($matches[3], '/') . '[-_ ]+([a-z0-9])$/i', $rawAlbum, $mExt)) {
                $model = $candModel . ' ' . $mExt[1];
            } elseif (!$model) {
                $model = $candModel;
            }

            $candAlbum = str_replace(['-', '_'], ' ', $rawAlbum);
            $candAlbum = preg_replace('/\s+' . preg_quote($candModel, '/') . '(\s+[a-z0-9])?$/i', '', $candAlbum);
            $albumName = trim($candAlbum);
        }
        // Pattern 4 (MetArt / ALS / RA / Goddess / ErroticaArchive / Domai with Photographer): Site_YYYY-MM-DD_ALBUM-MODEL-by-PHOTOG
        elseif (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_(.+?)[-_]by-(.+)$/i', $cleanBase, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            $rawRest = $matches[3];
            $photographer = str_replace(['-', '_'], ' ', $matches[4]);

            if ($model) {
                $candidateName = str_replace(['-', '_'], ' ', $rawRest);
                $escapedModel = preg_quote($model, '/');
                $candidateName = preg_replace('/' . preg_replace('/\s+/', '[-_\\s]+', $escapedModel) . '/i', '', $candidateName);
                $candidateName = trim(preg_replace('/\s+/', ' ', $candidateName));
                $albumName = !empty($candidateName) ? $candidateName : str_replace(['-', '_'], ' ', $rawRest);
            } else {
                $words = explode('-', $rawRest);
                $wCount = count($words);
                $foundModel = false;

                // Check duplicated model or MODEL-N-MODEL (e.g. LERA-F-LERA-F, KITTY-LOVEDREAM-2-KITTY-LOVEDREAM)
                for ($len = 1; $len <= floor($wCount / 2); $len++) {
                    $tail = implode(' ', array_slice($words, -$len));
                    $head = implode(' ', array_slice($words, 0, $len));
                    if (strcasecmp($tail, $head) === 0) {
                        $model = $tail;
                        $albumName = implode(' ', array_slice($words, 0, -$len));
                        $foundModel = true;
                        break;
                    }
                    $headMinusNum = preg_replace('/\s+\d+$/', '', $head);
                    if (strcasecmp($tail, $headMinusNum) === 0) {
                        $model = $tail;
                        $albumName = implode(' ', array_slice($words, 0, -$len));
                        $foundModel = true;
                        break;
                    }
                }

                if (!$foundModel && !empty($knownModels)) {
                    for ($kLen = min(4, $wCount - 1); $kLen >= 1; $kLen--) {
                        $testCand = strtolower(implode(' ', array_slice($words, -$kLen)));
                        if (isset($knownModels[$testCand])) {
                            $model = $knownModels[$testCand];
                            $albumName = implode(' ', array_slice($words, 0, -$kLen));
                            $foundModel = true;
                            break;
                        }
                    }
                }

                if (!$foundModel) {
                    if ($wCount >= 3 && preg_match('/^(i|ii|iii|iv|v|vi|1|2|3|4|5)$/i', $words[$wCount - 2])) {
                        $model = $words[$wCount - 1];
                        $albumName = implode(' ', array_slice($words, 0, -1));
                    } elseif ($wCount >= 3) {
                        $model = $words[$wCount - 2] . ' ' . $words[$wCount - 1];
                        $albumName = implode(' ', array_slice($words, 0, -2));
                    } elseif ($wCount === 2) {
                        $model = $words[1];
                        $albumName = $words[0];
                    } else {
                        $albumName = implode(' ', $words);
                    }
                }
            }
        }
        // Pattern 5 (MPLStudios Style): Mplstudios_2026-06-01_Hareniks_Meringue
        elseif (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_([a-z0-9\-]+)_(.+)$/i', $cleanBase, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            if (!$model) {
                $model = str_replace('-', ' ', $matches[3]);
            }
            $albumName = str_replace('-', ' ', $matches[4]);
        }
        // Pattern 6 (MetArt Style): [MetArt.com] 26-07-01 Andrea - Morning Temptation (x141) 4032x6048
        elseif (preg_match('/^\[([a-z0-9\.]+)\]\s+(\d{2}-\d{2}-\d{2})\s+([^\-]+)\s+-\s+(.+?)(?:\s+\(x\d+\))?(?:\s+\d+x\d+)?$/i', $cleanFolderName, $matches)) {
            $site = str_replace('.com', '', $matches[1]);
            $date = '20' . $matches[2];
            if (!$model) {
                $model = trim($matches[3]);
            }
            $albumName = trim($matches[4]);
            $albumName = preg_replace('/\s*\(x\d+\).*/i', '', $albumName);
            $albumName = preg_replace('/\s*\d+x\d+.*/i', '', $albumName);
        }
        // Pattern 7 (Generic Site and Date): SiteName_YYYY-MM-DD_...
        elseif (preg_match('/^([a-z0-9]+)_(\d{4}-\d{2}-\d{2})_(.+)$/i', $cleanBase, $matches)) {
            $site = $matches[1];
            $date = $matches[2];
            $albumName = str_replace('-', ' ', $matches[3]);
        }
        // Pattern 8: YYYY-MM-DD Model - Album Name (e.g. 2025-10-02 Beatrice - Presenting Beatrice)
        elseif (preg_match('/^(\d{4}-\d{2}-\d{2})\s+([^-]+)\s+-\s+(.+)$/i', $cleanFolderName, $matches)) {
            $date = $matches[1];
            if (!$model) {
                $model = trim($matches[2]);
            }
            $albumName = trim($matches[3]);
        }
        // Pattern 9: YY-MM-DD Model - Album Name
        elseif (preg_match('/^(\d{2}-\d{2}-\d{2})\s+([^-]+)\s+-\s+(.+)$/i', $cleanFolderName, $matches)) {
            $date = '20' . $matches[1];
            if (!$model) {
                $model = trim($matches[2]);
            }
            $albumName = trim($matches[3]);
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
        elseif ($siteLower === 'metartx') $site = 'MetArtX';
        elseif ($siteLower === 'mplstudios') $site = 'MPLStudios';
        elseif ($siteLower === 'watch4beauty') $site = 'Watch4Beauty';
        elseif ($siteLower === 'wowgirls') $site = 'Wowgirls';
        elseif ($siteLower === 'ultrafilms') $site = 'Ultrafilms';
        elseif ($siteLower === 'als') $site = 'ALS';
        elseif ($siteLower === 'ra') $site = 'RA';
        elseif ($siteLower === 'erroticaarchive') $site = 'ErroticaArchive';
        elseif ($siteLower === 'thelifeerotic') $site = 'TheLifeErotic';
        elseif ($siteLower === 'vivthomas') $site = 'VivThomas';
        elseif ($siteLower === 'goddess') $site = 'Goddess';
        elseif ($siteLower === 'domai') $site = 'Domai';
        elseif ($siteLower === 'eternal') $site = 'Eternal';
        elseif ($siteLower === 'sexart') $site = 'SexArt';
        elseif ($siteLower === 'straplez') $site = 'Straplez';
        elseif ($siteLower === 'stunning') $site = 'Stunning';
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

        $primaryModel = !empty($parsedModels) ? $parsedModels[0] : null;

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
            'optimized' => false,
            'models' => $parsedModels,
            'model_age' => $primaryModel['age'] ?? null,
            'model_country' => $primaryModel['country'] ?? null,
            'model_hair' => $primaryModel['hair'] ?? null,
            'model_eye' => $primaryModel['eye'] ?? null,
            'model_height' => $primaryModel['height'] ?? null,
            'model_weight' => $primaryModel['weight'] ?? null,
            'model_breast' => $primaryModel['breast'] ?? null,
            'model_measurements' => $primaryModel['measurements'] ?? null,
            'model_ethnicity' => $primaryModel['ethnicity'] ?? null,
            'model_bio' => $primaryModel['bio'] ?? null,
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

        // Security boundary validation across all active mapped paths
        $activePhotosPaths = $this->getActivePhotosPaths();
        $allowedPaths = array_map(function ($p) {
            return realpath(trim($p, " \t\n\r\0\x0B\"'"));
        }, $activePhotosPaths);

        $allowedHome = realpath('C:\\Users\\SaMuB\\Documents\\financeiro\\storage\\model');
        if ($allowedHome) {
            $allowedPaths[] = $allowedHome;
        }
        $allowedPaths = array_values(array_filter($allowedPaths));

        $isInsideAllowed = false;
        foreach ($allowedPaths as $allowed) {
            if ($allowed !== false && stripos($decodedPath, $allowed) === 0) {
                $isInsideAllowed = true;
                break;
            }
        }

        if (!$isInsideAllowed) {
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

        // Handle thumbnail or display optimized versions
        $type = $request->query('type');
        if ($type === 'thumb') {
            $servePath = $this->getOrCreateThumbnail($decodedPath);
            $mimeType = str_ends_with(strtolower($servePath), '.webp') ? 'image/webp' : 'image/jpeg';
        } elseif ($type === 'display') {
            $servePath = $this->getOrCreateDisplayImage($decodedPath);
            $mimeType = str_ends_with(strtolower($servePath), '.webp') ? 'image/webp' : 'image/jpeg';
        }

        return response()->file($servePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
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
                // Generate 320px WebP thumbnail on SSD for fast list rendering (removed 2K display preview)
                $this->getOrCreateThumbnail($file);

                echo json_encode([
                    'success' => true,
                    'photo' => basename($file),
                    'current' => $current,
                    'total' => $total,
                    'percent' => round(($current / $total) * 100),
                    'driver' => class_exists('Imagick') ? 'Imagick (WebP)' : 'GD (WebP)'
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
                            $item['optimized_at'] = date('Y-m-d H:i:s');
                            $item['optimized_count'] = count($files);
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
     * Get or create a high-fidelity display preview (max 2048px in WebP) on SSD
     * to eliminate decoding 24MP raw files in full-screen lightbox.
     */
    private function getOrCreateDisplayImage($originalPath)
    {
        $hash = md5($originalPath);
        $displayDir = storage_path('app/photo_display_cache');
        
        if (!is_dir($displayDir)) {
            @mkdir($displayDir, 0755, true);
        }
        
        $displayPathWebp = $displayDir . DIRECTORY_SEPARATOR . $hash . '.webp';
        if (file_exists($displayPathWebp)) {
            return $displayPathWebp;
        }

        // 1. Try Imagick (Fastest, multi-threaded C library, high quality WebP)
        if (class_exists('Imagick')) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($originalPath);
                
                if ($imagick->getImageAlphaChannel()) {
                    $imagick->setImageBackgroundColor('white');
                    $flat = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                    $imagick->clear();
                    $imagick->destroy();
                    $imagick = $flat;
                }
                
                $imagick->stripImage();
                $origWidth = $imagick->getImageWidth();
                $origHeight = $imagick->getImageHeight();
                
                // Only resize if original exceeds 2048px
                if ($origWidth > 2048 || $origHeight > 2048) {
                    $imagick->resizeImage(2048, 2048, \Imagick::FILTER_LANCZOS, 1, true);
                }
                
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality(85);
                $imagick->writeImage($displayPathWebp);
                $imagick->clear();
                $imagick->destroy();
                
                return $displayPathWebp;
            } catch (\Exception $e) {
                Log::warning('Imagick display generation failed for ' . $originalPath . ': ' . $e->getMessage() . '. Usando fallback GD.');
            }
        }

        // 2. Fallback to GD
        try {
            @ini_set('memory_limit', '512M');
            $ext = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            $image = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($originalPath),
                'png' => @imagecreatefrompng($originalPath),
                'webp' => @imagecreatefromwebp($originalPath),
                default => null,
            };

            if (!$image) {
                return $originalPath;
            }

            $w = imagesx($image);
            $h = imagesy($image);
            $maxDim = 2048;

            if ($w > $maxDim || $h > $maxDim) {
                if ($w > $h) {
                    $newW = $maxDim;
                    $newH = (int) floor($h * ($maxDim / $w));
                } else {
                    $newH = $maxDim;
                    $newW = (int) floor($w * ($maxDim / $h));
                }
            } else {
                $newW = $w;
                $newH = $h;
            }

            $display = imagecreatetruecolor($newW, $newH);
            $white = imagecolorallocate($display, 255, 255, 255);
            imagefill($display, 0, 0, $white);
            imagecopyresampled($display, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);

            if (function_exists('imagewebp')) {
                imagewebp($display, $displayPathWebp, 85);
            } else {
                $displayPathJpg = $displayDir . DIRECTORY_SEPARATOR . $hash . '.jpg';
                imagejpeg($display, $displayPathJpg, 85);
                imagedestroy($image);
                imagedestroy($display);
                return $displayPathJpg;
            }

            imagedestroy($image);
            imagedestroy($display);
            return $displayPathWebp;
        } catch (\Exception $e) {
            Log::error('Erro ao gerar display image: ' . $e->getMessage());
            return $originalPath;
        }
    }

    /**
     * Get or create a resized thumbnail (320px WebP) from a photo.
     */
    private function getOrCreateThumbnail($originalPath)
    {
        $hash = md5($originalPath);
        $thumbDir = storage_path('app/photo_thumbnails');
        
        if (!is_dir($thumbDir)) {
            @mkdir($thumbDir, 0755, true);
        }
        
        $thumbPathWebp = $thumbDir . DIRECTORY_SEPARATOR . $hash . '.webp';
        $thumbPathJpg = $thumbDir . DIRECTORY_SEPARATOR . $hash . '.jpg';
        
        if (file_exists($thumbPathWebp)) {
            return $thumbPathWebp;
        }
        if (file_exists($thumbPathJpg)) {
            return $thumbPathJpg;
        }
        
        // 1. Try using Imagick (fast WebP generation)
        if (class_exists('Imagick')) {
            try {
                $imagick = new \Imagick();
                $imagick->readImage($originalPath);
                
                if ($imagick->getImageAlphaChannel()) {
                    $imagick->setImageBackgroundColor('white');
                    $flat = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                    $imagick->clear();
                    $imagick->destroy();
                    $imagick = $flat;
                }
                
                $imagick->stripImage();
                $imagick->thumbnailImage(320, 320, true);
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality(75);
                
                $imagick->writeImage($thumbPathWebp);
                $imagick->clear();
                $imagick->destroy();
                
                return $thumbPathWebp;
            } catch (\Exception $e) {
                Log::warning('Erro Imagick thumbnail para ' . $originalPath . ': ' . $e->getMessage() . '. Fallback para GD.');
            }
        }
        
        // 2. Fallback to GD
        try {
            @ini_set('memory_limit', '512M');
            $extension = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
            $image = match ($extension) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($originalPath),
                'png' => @imagecreatefrompng($originalPath),
                'webp' => @imagecreatefromwebp($originalPath),
                default => null,
            };
            
            if (!$image) {
                return $originalPath;
            }
            
            $width = imagesx($image);
            $height = imagesy($image);
            $maxSize = 320;
            if ($width > $height) {
                $newWidth = $maxSize;
                $newHeight = (int) floor($height * ($maxSize / $width));
            } else {
                $newHeight = $maxSize;
                $newWidth = (int) floor($width * ($maxSize / $height));
            }
            
            $newWidth = max(1, $newWidth);
            $newHeight = max(1, $newHeight);
            
            $thumb = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefill($thumb, 0, 0, $white);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            if (function_exists('imagewebp')) {
                imagewebp($thumb, $thumbPathWebp, 75);
                imagedestroy($image);
                imagedestroy($thumb);
                return $thumbPathWebp;
            } else {
                imagejpeg($thumb, $thumbPathJpg, 70);
                imagedestroy($image);
                imagedestroy($thumb);
                return $thumbPathJpg;
            }
        } catch (\Exception $e) {
            Log::error('Erro ao gerar miniatura: ' . $e->getMessage());
            return $originalPath;
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
     * Delete album permanently from PC storage and cache.
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

        $albumRawPath = $albumToDelete['path'] ?? '';
        $albumPath = realpath($albumRawPath) ?: $albumRawPath;
        if (!file_exists($albumPath)) {
            // Directory is already gone from disk, just remove from cache
            array_splice($cacheData['albums'], $albumIndex, 1);
            file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));
            return response()->json([
                'success' => true, 
                'message' => 'Álbum removido do cache pois já não existia no disco.',
                'id' => $id
            ]);
        }

        // Security boundaries check
        $activePhotosPaths = $this->getActivePhotosPaths();
        if (!empty($albumToDelete['source_path'])) {
            $activePhotosPaths[] = $albumToDelete['source_path'];
        }

        $allowedHome = realpath('C:\\Users\\SaMuB\\Documents\\financeiro\\storage\\model');
        if ($allowedHome) {
            $activePhotosPaths[] = $allowedHome;
        }

        // Normalize paths for Windows (case-insensitive, uniform DIRECTORY_SEPARATOR)
        $albumNorm = strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $albumPath), DIRECTORY_SEPARATOR));
        
        $isInsideAllowed = false;
        $isRootAllowed = false;

        foreach ($activePhotosPaths as $rawAllowed) {
            $trimmed = trim($rawAllowed, " \t\n\r\0\x0B\"'");
            if (empty($trimmed)) continue;

            $allowedReal = realpath($trimmed) ?: $trimmed;
            $allowedNorm = strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $allowedReal), DIRECTORY_SEPARATOR));
            
            if (!empty($allowedNorm)) {
                if ($albumNorm === $allowedNorm) {
                    $isRootAllowed = true;
                    break;
                } elseif (str_starts_with($albumNorm, $allowedNorm . DIRECTORY_SEPARATOR)) {
                    $isInsideAllowed = true;
                }
            }
        }

        if ($isRootAllowed) {
            return response()->json(['success' => false, 'message' => 'Não é permitido excluir o diretório raiz configurado para as fotos.'], 403);
        }

        if (!$isInsideAllowed) {
            return response()->json(['success' => false, 'message' => 'Acesso não autorizado para excluir este diretório fora das pastas permitidas.'], 403);
        }

        // 1. Delete associated cached thumbnails and display images
        try {
            $thumbDir = storage_path('app/photo_thumbnails');
            $displayDir = storage_path('app/photo_display');
            $files = $this->safeGlobImages($albumPath);
            foreach ($files as $file) {
                $hash = md5($file);
                @unlink($thumbDir . DIRECTORY_SEPARATOR . $hash . '.webp');
                @unlink($thumbDir . DIRECTORY_SEPARATOR . $hash . '.jpg');
                @unlink($displayDir . DIRECTORY_SEPARATOR . $hash . '.webp');
                @unlink($displayDir . DIRECTORY_SEPARATOR . $hash . '.jpg');
            }
            if (!empty($albumToDelete['cover_image'])) {
                $coverHash = md5($albumPath . DIRECTORY_SEPARATOR . $albumToDelete['cover_image']);
                @unlink($thumbDir . DIRECTORY_SEPARATOR . $coverHash . '.webp');
                @unlink($thumbDir . DIRECTORY_SEPARATOR . $coverHash . '.jpg');
            }
        } catch (\Throwable $e) {
            Log::warning('Erro ao limpar cache de miniaturas: ' . $e->getMessage());
        }

        // 2. Delete folder recursively from disk
        try {
            if (is_dir($albumPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($albumPath);
            }
            // On Windows, read-only files might require shell rmdir fallback
            if (is_dir($albumPath) && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @exec('rmdir /s /q ' . escapeshellarg($albumPath));
            }
            if (is_dir($albumPath)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Não foi possível remover completamente a pasta do disco. Verifique permissões de arquivos.'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Erro ao excluir pasta do álbum ' . $albumPath . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao excluir arquivos do disco: ' . $e->getMessage()], 500);
        }

        // 3. Remove album from cache
        array_splice($cacheData['albums'], $albumIndex, 1);
        file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));

        return response()->json([
            'success' => true, 
            'message' => 'Álbum e todos os seus arquivos foram excluídos permanentemente do computador!',
            'id' => $id
        ]);
    }

    /**
     * Open the album's physical folder on PC (Windows Explorer).
     */
    public function openFolder(Request $request, $id)
    {
        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return response()->json(['success' => false, 'message' => 'Cache de fotos não encontrado.'], 404);
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];

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

        $albumRawPath = $album['path'] ?? '';
        $albumPath = realpath($albumRawPath) ?: $albumRawPath;
        if (!$albumPath || !is_dir($albumPath)) {
            return response()->json(['success' => false, 'message' => 'Pasta do álbum não foi encontrada no disco deste PC.'], 404);
        }

        // Security boundaries check
        $activePhotosPaths = $this->getActivePhotosPaths();
        if (!empty($album['source_path'])) {
            $activePhotosPaths[] = $album['source_path'];
        }

        $allowedHome = realpath('C:\\Users\\SaMuB\\Documents\\financeiro\\storage\\model');
        if ($allowedHome) {
            $activePhotosPaths[] = $allowedHome;
        }

        $albumNorm = strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $albumPath), DIRECTORY_SEPARATOR));
        $isInsideAllowed = false;

        foreach ($activePhotosPaths as $rawAllowed) {
            $trimmed = trim($rawAllowed, " \t\n\r\0\x0B\"'");
            if (empty($trimmed)) continue;

            $allowedReal = realpath($trimmed) ?: $trimmed;
            $allowedNorm = strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $allowedReal), DIRECTORY_SEPARATOR));

            if (!empty($allowedNorm) && stripos($albumNorm, $allowedNorm) === 0) {
                $isInsideAllowed = true;
                break;
            }
        }

        if (!$isInsideAllowed) {
            return response()->json(['success' => false, 'message' => 'Acesso não autorizado para abrir esta pasta.'], 403);
        }

        try {
            if (app()->environment('testing')) {
                // Nos testes não abre o Explorador de verdade (a pasta temporária é apagada em seguida).
            } elseif (PHP_OS_FAMILY === 'Windows') {
                pclose(popen('explorer.exe ' . escapeshellarg($albumPath), 'r'));
            } elseif (PHP_OS_FAMILY === 'Darwin') {
                @exec('open ' . escapeshellarg($albumPath));
            } else {
                @exec('xdg-open ' . escapeshellarg($albumPath));
            }

            return response()->json([
                'success' => true,
                'path' => $albumPath,
                'message' => 'Pasta aberta com sucesso no Explorador de Arquivos do PC!'
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao abrir pasta no Explorador: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao abrir pasta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick-scan all album directories to enrich/update model info from .txt files without full file re-indexing.
     */
    public function rescanModels(Request $request)
    {
        $cachePath = config('photos.cache_path');
        if (!file_exists($cachePath)) {
            return response()->json(['success' => false, 'message' => 'Cache de fotos não encontrado.'], 400);
        }

        $cacheData = json_decode(file_get_contents($cachePath), true) ?? [];
        $albums = $cacheData['albums'] ?? [];
        $updatedCount = 0;

        foreach ($albums as &$album) {
            $albumPath = $album['path'] ?? null;
            if (!$albumPath || !is_dir($albumPath)) {
                continue;
            }

            $models = $this->parseModelBioFiles($albumPath);
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
            } elseif (empty($album['model'])) {
                // Try to infer model from folder name
                $folder = $album['folder_name'] ?? '';
                if (preg_match('/^\d{4}-\d{2}-\d{2}\s+([^-]+)\s+-/i', $folder, $m)) {
                    $album['model'] = ucwords(strtolower(trim($m[1])));
                    $updatedCount++;
                }
            }

            // Re-parse folder details if model is empty, or album has unnormalized/hash title, or is Watch4Beauty
            $folder = $album['folder_name'] ?? '';
            if (empty($album['model']) || stripos($folder, 'watch4beauty') !== false || preg_match('/(_med|_mid|_low|_high|_large)$/i', $album['album_name'] ?? '') || preg_match('/^[a-f0-9]{4,10}_/i', $album['album_name'] ?? '')) {
                $parsed = $this->parseAlbumFolder($albumPath, $folder, $album['file_count'] ?? 0, $album['cover_image'] ?? '');
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
        unset($album);

        $cacheData['albums'] = $albums;
        file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Varredura de modelos concluída! {$updatedCount} álbuns atualizados com informações biográficas.",
                'updated_count' => $updatedCount
            ]);
        }

        return redirect()->route('photos.index')->with('success', "Varredura concluída! {$updatedCount} álbuns atualizados com dados das modelos.");
    }
}
