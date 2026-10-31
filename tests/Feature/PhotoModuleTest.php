<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PhotoModuleTest extends TestCase
{
    public function test_photo_gallery_requires_authentication()
    {
        // 1. Without being authenticated as user, should redirect to generic login
        $response = $this->get('/photos');
        $response->assertRedirect('/login');

        // 2. Authenticated as user but without photos_authenticated session, should redirect to /photos/login
        $user = User::factory()->make(['id' => 1]);
        $response = $this->actingAs($user)->get('/photos');
        $response->assertRedirect('/photos/login');
    }

    public function test_authenticated_user_can_access_photo_gallery()
    {
        $user = User::factory()->make(['id' => 1]);

        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_index_' . uniqid() . '.json';
        file_put_contents($testCachePath, json_encode([
            'last_sync' => now()->toIso8601String(),
            'albums' => []
        ]));

        $oldCachePath = config('photos.cache_path');
        config(['photos.cache_path' => $testCachePath]);

        try {
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos');

            $response->assertStatus(200);
        } finally {
            config(['photos.cache_path' => $oldCachePath]);
            if (file_exists($testCachePath)) {
                @unlink($testCachePath);
            }
        }
    }

    public function test_sync_prioritizes_cover_images()
    {
        // 1. Create a temporary folder structure
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_photo_gallery_' . uniqid();
        mkdir($tempPath);
        $tempPath = realpath($tempPath);

        $albumPath = $tempPath . DIRECTORY_SEPARATOR . 'Femjoy_2026-06-01_GETTING-HOT_SUMIKO_by-DAVID-EKMEKCI';
        mkdir($albumPath);
        $albumPath = realpath($albumPath);

        // 2. Put some image files there
        // One standard image
        $image1 = $albumPath . DIRECTORY_SEPARATOR . '01.jpg';
        file_put_contents($image1, 'fake image data');

        // One cover image (should be selected as cover)
        $coverImage = $albumPath . DIRECTORY_SEPARATOR . '_cover.jpg';
        file_put_contents($coverImage, 'fake cover data');

        // Another standard image
        $image2 = $albumPath . DIRECTORY_SEPARATOR . '02.jpg';
        file_put_contents($image2, 'fake image data');

        // 3. Set the config dynamically (use a dedicated test cache file path)
        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_test_' . uniqid() . '.json';

        config([
            'photos.path' => $tempPath,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $user = User::factory()->make(['id' => 1]);

            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync');

            $response->assertRedirect('/photos');

            // 4. Verify cache content in the temporary test cache file
            $this->assertFileExists($testCachePath);

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $albums = $cacheData['albums'] ?? [];

            // Find the specific album in cache
            $found = null;
            foreach ($albums as $album) {
                if (realpath($album['path']) === $albumPath) {
                    $found = $album;
                    break;
                }
            }

            $this->assertNotNull($found, 'Album not found in cache');
            $this->assertEquals('_cover.jpg', $found['cover_image']);
        } finally {
            // Cleanup files
            @unlink($image1);
            @unlink($coverImage);
            @unlink($image2);
            @rmdir($albumPath);
            @rmdir($tempPath);

            // Restore config
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_sync_falls_back_to_first_image_when_no_cover_exists()
    {
        // 1. Create a temporary folder structure
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_photo_gallery_' . uniqid();
        mkdir($tempPath);
        $tempPath = realpath($tempPath);

        $albumPath = $tempPath . DIRECTORY_SEPARATOR . 'Femjoy_2026-06-01_GETTING-HOT_SUMIKO_by-DAVID-EKMEKCI';
        mkdir($albumPath);
        $albumPath = realpath($albumPath);

        // 2. Put some image files there (no cover image)
        $image1 = $albumPath . DIRECTORY_SEPARATOR . '01.jpg';
        file_put_contents($image1, 'fake image data');

        $image2 = $albumPath . DIRECTORY_SEPARATOR . '02.jpg';
        file_put_contents($image2, 'fake image data');

        // 3. Set the config dynamically (use a dedicated test cache file path)
        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_test_' . uniqid() . '.json';

        config([
            'photos.path' => $tempPath,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $user = User::factory()->make(['id' => 1]);

            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync');

            $response->assertRedirect('/photos');

            // 4. Verify cache content in the temporary test cache file
            $this->assertFileExists($testCachePath);

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $albums = $cacheData['albums'] ?? [];

            // Find the specific album in cache
            $found = null;
            foreach ($albums as $album) {
                if (realpath($album['path']) === $albumPath) {
                    $found = $album;
                    break;
                }
            }

            $this->assertNotNull($found, 'Album not found in cache');
            $this->assertEquals('01.jpg', $found['cover_image']);
        } finally {
            // Cleanup files
            @unlink($image1);
            @unlink($image2);
            @rmdir($albumPath);
            @rmdir($tempPath);

            // Restore config
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_pregenerate_thumbs_requires_authentication()
    {
        // JSON request without authentication returns 401
        $response = $this->postJson('/photos/pregenerate-thumbs', ['id' => 'some-id']);
        $response->assertStatus(401);

        // Standard request without authentication redirects to /login
        $response2 = $this->post('/photos/pregenerate-thumbs', ['id' => 'some-id']);
        $response2->assertRedirect('/login');
    }

    public function test_pregenerate_thumbs_generates_thumbnails()
    {
        // 1. Create a temporary folder structure
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_photo_gallery_' . uniqid();
        mkdir($tempPath);
        $tempPath = realpath($tempPath);

        $albumPath = $tempPath . DIRECTORY_SEPARATOR . 'Femjoy_2026-06-01_GETTING-HOT_SUMIKO_by-DAVID-EKMEKCI';
        mkdir($albumPath);
        $albumPath = realpath($albumPath);

        // 2. Put some image files there
        $image1 = $albumPath . DIRECTORY_SEPARATOR . '01.jpg';
        file_put_contents($image1, 'fake image data');

        $image2 = $albumPath . DIRECTORY_SEPARATOR . '02.jpg';
        file_put_contents($image2, 'fake image data');

        // 3. Set the config dynamically
        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_test_' . uniqid() . '.json';

        config([
            'photos.path' => $tempPath,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $user = User::factory()->make(['id' => 1]);

            // Sync first to build the cache
            $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync');

            // Find album id
            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $albumId = $cacheData['albums'][0]['id'];

            // Run pre-generation
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->postJson('/photos/pregenerate-thumbs', ['id' => $albumId]);

            $response->assertStatus(200);
            
            $content = $response->streamedContent();
            $this->assertStringContainsString('"success":true', $content);
            $this->assertStringContainsString('"photo":"01.jpg"', $content);
            $this->assertStringContainsString('"photo":"02.jpg"', $content);
            $this->assertStringContainsString('"current":1', $content);
            $this->assertStringContainsString('"current":2', $content);
            $this->assertStringContainsString('"total":2', $content);
        } finally {
            // Cleanup files
            @unlink($image1);
            @unlink($image2);
            @rmdir($albumPath);
            @rmdir($tempPath);

            // Restore config
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_serve_photo_supports_display_and_thumb_types()
    {
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_photo_serve_' . uniqid();
        mkdir($tempPath);
        $albumPath = $tempPath . DIRECTORY_SEPARATOR . 'Album_2026-06-01_Test';
        mkdir($albumPath);

        // Create a real tiny 100x100 jpeg image
        $imagePath = $albumPath . DIRECTORY_SEPARATOR . 'test.jpg';
        $im = imagecreatetruecolor(100, 100);
        $red = imagecolorallocate($im, 255, 0, 0);
        imagefill($im, 0, 0, $red);
        imagejpeg($im, $imagePath);
        imagedestroy($im);

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_serve_' . uniqid() . '.json';

        config([
            'photos.path' => $tempPath,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $user = User::factory()->make(['id' => 1]);

            // Sync first
            $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync');

            $encodedPath = base64_encode($imagePath);

            // Test type=thumb
            $thumbResponse = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos/file?path=' . $encodedPath . '&type=thumb');
            $thumbResponse->assertStatus(200);
            $this->assertStringContainsString('image/', $thumbResponse->headers->get('Content-Type'));

            // Test type=display
            $displayResponse = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos/file?path=' . $encodedPath . '&type=display');
            $displayResponse->assertStatus(200);
            $this->assertStringContainsString('image/', $displayResponse->headers->get('Content-Type'));
        } finally {
            @unlink($imagePath);
            @rmdir($albumPath);
            @rmdir($tempPath);
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_sync_with_multiple_paths()
    {
        $temp1 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_multi_1_' . uniqid();
        $temp2 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_multi_2_' . uniqid();
        mkdir($temp1);
        mkdir($temp2);

        $album1 = $temp1 . DIRECTORY_SEPARATOR . 'Femjoy_2026-06-01_AlbumOne_ModelA_by-PhotogA';
        $album2 = $temp2 . DIRECTORY_SEPARATOR . 'MetArt_2026-06-02_AlbumTwo_ModelB_by-PhotogB';
        mkdir($album1);
        mkdir($album2);

        $img1 = $album1 . DIRECTORY_SEPARATOR . 'cover.jpg';
        $img2 = $album2 . DIRECTORY_SEPARATOR . 'cover.jpg';
        file_put_contents($img1, 'img1');
        file_put_contents($img2, 'img2');

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_multi_' . uniqid() . '.json';

        config([
            'photos.paths' => [$temp1, $temp2],
            'photos.path' => $temp1 . ';' . $temp2,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $user = User::factory()->make(['id' => 1]);

            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync', [
                    'photos_paths' => [$temp1, $temp2]
                ]);

            $response->assertRedirect('/photos');

            $this->assertFileExists($testCachePath);
            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $albums = $cacheData['albums'] ?? [];

            $this->assertCount(2, $albums);

            $pathsInCache = array_map(fn($a) => realpath($a['path']), $albums);
            $this->assertContains(realpath($album1), $pathsInCache);
            $this->assertContains(realpath($album2), $pathsInCache);

            // Verify source_path
            foreach ($albums as $alb) {
                if (realpath($alb['path']) === realpath($album1)) {
                    $this->assertEquals(realpath($temp1), realpath($alb['source_path']));
                } elseif (realpath($alb['path']) === realpath($album2)) {
                    $this->assertEquals(realpath($temp2), realpath($alb['source_path']));
                }
            }
        } finally {
            @unlink($img1);
            @unlink($img2);
            @rmdir($album1);
            @rmdir($album2);
            @rmdir($temp1);
            @rmdir($temp2);
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_add_and_remove_location()
    {
        $loc1 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'loc_1_' . uniqid();
        $loc2 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'loc_2_' . uniqid();
        mkdir($loc1);
        mkdir($loc2);

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_loc_' . uniqid() . '.json';

        config([
            'photos.path' => $loc1,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $user = User::factory()->make(['id' => 1]);

            // Initial sync with loc1
            $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync', ['photos_path' => $loc1]);

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $this->assertEquals([$loc1], $cacheData['photos_paths']);

            // Add loc2
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/locations/add', ['path' => $loc2]);

            $response->assertRedirect('/photos');

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $this->assertCount(2, $cacheData['photos_paths']);
            $this->assertContains($loc1, $cacheData['photos_paths']);
            $this->assertContains($loc2, $cacheData['photos_paths']);

            // Remove loc2
            $removeResponse = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/locations/remove', ['path' => $loc2]);

            $removeResponse->assertRedirect('/photos');

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $this->assertCount(1, $cacheData['photos_paths']);
            $this->assertEquals([$loc1], $cacheData['photos_paths']);

            // Remove last remaining location (loc1)
            $removeLastResponse = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/locations/remove', ['path' => $loc1]);

            $removeLastResponse->assertRedirect('/photos');

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $this->assertCount(0, $cacheData['photos_paths']);
        } finally {
            @rmdir($loc1);
            @rmdir($loc2);
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_location_directly_containing_images_is_detected_as_album()
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'init_base_' . uniqid();
        $loc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'direct_album_' . uniqid();
        mkdir($base);
        mkdir($loc);

        $img = $loc . DIRECTORY_SEPARATOR . 'test.jpg';
        file_put_contents($img, 'test direct image data');

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_direct_' . uniqid() . '.json';

        config([
            'photos.path' => $base,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $user = User::factory()->make(['id' => 1]);

            // Initial sync
            $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync', ['photos_path' => $base]);

            // Add the direct album location
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/locations/add', ['path' => $loc]);

            $response->assertRedirect('/photos');

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $this->assertContains($loc, $cacheData['photos_paths']);
            $this->assertCount(1, $cacheData['albums']);
            $this->assertEquals('test.jpg', $cacheData['albums'][0]['cover_image']);
        } finally {
            @unlink($img);
            @rmdir($loc);
            @rmdir($base);
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_parse_model_bio_files_extracts_all_attributes()
    {
        $controller = new \App\Http\Controllers\PhotoController();
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'album_bio_test_' . uniqid();
        mkdir($tempDir);

        $bioFile = $tempDir . DIRECTORY_SEPARATOR . 'model-bio_Jane-Doe.txt';
        $bioContent = "Jane Doe\nAge when shot: 23\nCountry: United States\nHair color: blonde\nEye color: blue\nHeight: 175cm\nWeight: 52kg\nBreast size: medium\nMeasurements: 86/61/89\nEthnicity: Caucasian\n\nBio: Jane Doe is a talented artist from California.";
        file_put_contents($bioFile, $bioContent);

        try {
            $models = $controller->parseModelBioFiles($tempDir);

            $this->assertCount(1, $models);
            $model = $models[0];
            $this->assertEquals('Jane Doe', $model['name']);
            $this->assertEquals(23, $model['age']);
            $this->assertEquals('United States', $model['country']);
            $this->assertEquals('Blonde', $model['hair']);
            $this->assertEquals('Blue', $model['eye']);
            $this->assertEquals('175cm', $model['height']);
            $this->assertEquals('52kg', $model['weight']);
            $this->assertEquals('Medium', $model['breast']);
            $this->assertEquals('86/61/89', $model['measurements']);
            $this->assertEquals('Caucasian', $model['ethnicity']);
            $this->assertStringContainsString('Jane Doe is a talented artist', $model['bio']);
        } finally {
            @unlink($bioFile);
            @rmdir($tempDir);
        }
    }

    public function test_delete_album_permanently_removes_directory_and_updates_cache()
    {
        $user = User::factory()->make(['id' => 1]);

        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_root_' . uniqid();
        mkdir($base);
        $base = realpath($base);

        $albumDir = $base . DIRECTORY_SEPARATOR . '2026-01-01 Test Model - Summer Walk';
        mkdir($albumDir);
        $albumDir = realpath($albumDir);

        $photo1 = $albumDir . DIRECTORY_SEPARATOR . 'photo1.jpg';
        file_put_contents($photo1, 'image bytes');
        $bioTxt = $albumDir . DIRECTORY_SEPARATOR . 'model-bio_Test-Model.txt';
        file_put_contents($bioTxt, "Test Model\nAge when shot: 25\nCountry: Brazil\n");

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_del_' . uniqid() . '.json';

        config([
            'photos.path' => $base,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            // Initial sync to register the album in cache
            $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync', ['photos_path' => $base]);

            $this->assertDirectoryExists($albumDir);
            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $this->assertCount(1, $cacheData['albums']);
            $albumId = $cacheData['albums'][0]['id'];

            // Now permanently delete the album
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->postJson("/photos/album/{$albumId}/delete");

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            // Assert directory is permanently gone from disk
            $this->assertDirectoryDoesNotExist($albumDir);

            // Assert album is removed from cache file
            $newCache = json_decode(file_get_contents($testCachePath), true);
            $this->assertCount(0, $newCache['albums']);
        } finally {
            @unlink($photo1);
            @unlink($bioTxt);
            if (is_dir($albumDir)) {
                @rmdir($albumDir);
            }
            if (is_dir($base)) {
                @rmdir($base);
            }
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_delete_album_prevents_deleting_root_storage_directory()
    {
        $user = User::factory()->make(['id' => 1]);

        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_root_secure_' . uniqid();
        mkdir($base);
        $base = realpath($base);

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_sec_' . uniqid() . '.json';

        config([
            'photos.path' => $base,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            // Write a malicious cache entry pointing directly to the root photos path
            $rootId = md5($base);
            $cacheContent = [
                'last_sync' => date('Y-m-d H:i:s'),
                'photos_paths' => [$base],
                'photos_path' => $base,
                'albums' => [
                    [
                        'id' => $rootId,
                        'path' => $base,
                        'folder_name' => basename($base),
                        'site' => 'Root',
                        'date' => '2026-01-01',
                        'album_name' => 'Root Folder',
                        'cover_image' => 'none.jpg',
                        'file_count' => 0
                    ]
                ]
            ];
            file_put_contents($testCachePath, json_encode($cacheContent));

            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->postJson("/photos/album/{$rootId}/delete");

            // Must reject with 403 Forbidden
            $response->assertStatus(403);
            $this->assertDirectoryExists($base);
        } finally {
            if (is_dir($base)) {
                @rmdir($base);
            }
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_rescan_models_endpoint_updates_model_information()
    {
        $user = User::factory()->make(['id' => 1]);

        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_rescan_' . uniqid();
        mkdir($base);
        $base = realpath($base);

        $albumDir = $base . DIRECTORY_SEPARATOR . 'Album_Without_Initial_Bio';
        mkdir($albumDir);
        $albumDir = realpath($albumDir);

        $img = $albumDir . DIRECTORY_SEPARATOR . 'cover.jpg';
        file_put_contents($img, 'img data');

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_rescan_' . uniqid() . '.json';

        config([
            'photos.path' => $base,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            // Initial sync before .txt exists
            $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->post('/photos/sync', ['photos_path' => $base]);

            $cacheData = json_decode(file_get_contents($testCachePath), true);
            $this->assertCount(1, $cacheData['albums']);
            $this->assertNull($cacheData['albums'][0]['model_age'] ?? null);

            // Now create model bio .txt inside album
            $bioTxt = $albumDir . DIRECTORY_SEPARATOR . 'model-bio_Ashley.txt';
            file_put_contents($bioTxt, "Ashley\nAge when shot: 21\nCountry: France\nHair color: blonde\nBreast size: small\n");

            // Trigger rescan
            $rescanResponse = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->postJson('/photos/rescan-models');

            $rescanResponse->assertStatus(200);
            $rescanResponse->assertJson(['success' => true]);

            // Verify cache updated with Ashley's bio details
            $updatedCache = json_decode(file_get_contents($testCachePath), true);
            $this->assertEquals('Ashley', $updatedCache['albums'][0]['model']);
            $this->assertEquals(21, $updatedCache['albums'][0]['model_age']);
            $this->assertEquals('France', $updatedCache['albums'][0]['model_country']);
            $this->assertEquals('Blonde', $updatedCache['albums'][0]['model_hair']);
            $this->assertEquals('Small', $updatedCache['albums'][0]['model_breast']);
        } finally {
            @unlink($img);
            if (isset($bioTxt)) @unlink($bioTxt);
            if (is_dir($albumDir)) @rmdir($albumDir);
            if (is_dir($base)) @rmdir($base);
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_parse_album_folder_maps_watch4beauty_dotted_format()
    {
        $controller = new \App\Http\Controllers\PhotoController();
        $reflector = new \ReflectionClass(\App\Http\Controllers\PhotoController::class);
        $parseMethod = $reflector->getMethod('parseAlbumFolder');
        $parseMethod->setAccessible(true);

        $fakePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Watch4beauty_26.09.05.irene.rouse.my.sexy.legs_med';
        $result = $parseMethod->invoke($controller, $fakePath, 'Watch4beauty_26.09.05.irene.rouse.my.sexy.legs_med', 10, 'cover.jpg');

        $this->assertEquals('Watch4Beauty', $result['site']);
        $this->assertEquals('2026-09-05', $result['date']);
        $this->assertEquals('Irene Rouse', $result['model']);
        $this->assertEquals('My Sexy Legs', $result['album_name']);
    }

    public function test_parse_album_folder_maps_femjoy_large_and_stunning_formats()
    {
        $controller = new \App\Http\Controllers\PhotoController();
        $reflector = new \ReflectionClass(\App\Http\Controllers\PhotoController::class);
        $parseMethod = $reflector->getMethod('parseAlbumFolder');
        $parseMethod->setAccessible(true);

        // Femjoy with _large
        $resultFemjoy = $parseMethod->invoke(
            $controller,
            '/fake/Femjoy_2026-09-01_LIGHT-BLUE_LUNA-MOONIE_by-TOM-LEONARD_large',
            'Femjoy_2026-09-01_LIGHT-BLUE_LUNA-MOONIE_by-TOM-LEONARD_large',
            15,
            'cover.jpg'
        );
        $this->assertEquals('Femjoy', $resultFemjoy['site']);
        $this->assertEquals('2026-09-01', $resultFemjoy['date']);
        $this->assertEquals('Light Blue', $resultFemjoy['album_name']);
        $this->assertEquals('Luna Moonie', $resultFemjoy['model']);
        $this->assertEquals('Tom Leonard', $resultFemjoy['photographer']);

        // Stunning with ---
        $resultStunning = $parseMethod->invoke(
            $controller,
            '/fake/Stunning_2026-01-30_ADRIENNE---GET-NAKED-ON-THE-COUCH-ADRIENNE-W-by-THIERRY-MURRELL_d5640_med',
            'Stunning_2026-01-30_ADRIENNE---GET-NAKED-ON-THE-COUCH-ADRIENNE-W-by-THIERRY-MURRELL_d5640_med',
            20,
            'cover.jpg'
        );
        $this->assertEquals('Stunning', $resultStunning['site']);
        $this->assertEquals('2026-01-30', $resultStunning['date']);
        $this->assertEquals('Adrienne W', $resultStunning['model']);
        $this->assertEquals('Get Naked On The Couch', $resultStunning['album_name']);
        $this->assertEquals('Thierry Murrell', $resultStunning['photographer']);
    }

    public function test_open_folder_requires_authentication()
    {
        $response = $this->postJson('/photos/album/fake-id/open-folder');
        $this->assertTrue($response->isRedirection() || $response->status() === 401);
    }

    public function test_open_folder_opens_directory_and_returns_success()
    {
        $user = User::factory()->make(['id' => 1]);

        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_open_' . uniqid();
        mkdir($base);
        $base = realpath($base);

        $albumDir = $base . DIRECTORY_SEPARATOR . 'Test-Album';
        mkdir($albumDir);
        $albumDir = realpath($albumDir);

        $oldPhotosPath = config('photos.path');
        $oldCachePath = config('photos.cache_path');
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_open_' . uniqid() . '.json';

        $albumId = md5($albumDir);
        file_put_contents($testCachePath, json_encode([
            'last_sync' => now()->toIso8601String(),
            'photos_paths' => [$base],
            'albums' => [
                [
                    'id' => $albumId,
                    'path' => $albumDir,
                    'album_name' => 'Test Album',
                    'folder_name' => 'Test-Album',
                    'file_count' => 1,
                    'cover_image' => 'photo1.jpg'
                ]
            ]
        ]));

        config([
            'photos.path' => $base,
            'photos.cache_path' => $testCachePath
        ]);

        try {
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->postJson("/photos/album/{$albumId}/open-folder");

            $response->assertStatus(200);
            $response->assertJson(['success' => true]);
            $this->assertStringContainsString('Test-Album', $response->json('path'));
        } finally {
            if (is_dir($albumDir)) @rmdir($albumDir);
            if (is_dir($base)) @rmdir($base);
            config([
                'photos.path' => $oldPhotosPath,
                'photos.cache_path' => $oldCachePath
            ]);
            @unlink($testCachePath);
        }
    }

    public function test_combined_search_matches_multiple_terms_across_fields()
    {
        $user = User::factory()->make(['id' => 1]);
        $testCachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'photos_cache_combined_' . uniqid() . '.json';

        $albums = [
            [
                'id' => 'alb-1',
                'album_name' => 'My Sexy Legs',
                'folder_name' => 'Watch4beauty_26.09.05.irene.rouse.my.sexy.legs_med',
                'model' => 'Irene Rouse',
                'site' => 'Watch4Beauty',
                'date' => '2026-09-05',
                'year' => '2026',
                'month' => '09',
                'model_country' => 'Estados Unidos',
                'model_hair' => 'Loiro',
                'file_count' => 10,
                'cover_image' => 'cover.jpg',
                'path' => sys_get_temp_dir(),
            ],
            [
                'id' => 'alb-2',
                'album_name' => 'Golden Sunlight',
                'folder_name' => 'Femjoy_2026-05-10_GOLDEN-SUNLIGHT_SUMIKO_large',
                'model' => 'Sumiko',
                'site' => 'Femjoy',
                'date' => '2026-05-10',
                'year' => '2026',
                'month' => '05',
                'model_country' => 'Japão',
                'model_hair' => 'Preto',
                'file_count' => 15,
                'cover_image' => 'cover.jpg',
                'path' => sys_get_temp_dir(),
            ],
            [
                'id' => 'alb-3',
                'album_name' => 'Morning Breeze',
                'folder_name' => 'MetArt_2026-01-01_Alina_large',
                'model' => 'Alina',
                'site' => 'MetArt',
                'date' => '2026-01-01',
                'year' => '2026',
                'month' => '01',
                'model_country' => 'Rússia',
                'model_hair' => 'Castanho',
                'file_count' => 20,
                'cover_image' => 'cover.jpg',
                'path' => sys_get_temp_dir(),
            ]
        ];

        file_put_contents($testCachePath, json_encode([
            'last_sync' => now()->toIso8601String(),
            'albums' => $albums
        ]));

        $oldCachePath = config('photos.cache_path');
        config(['photos.cache_path' => $testCachePath]);

        try {
            // 1. Multi-term search: 'irene legs' (irene is model, legs is album name)
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos?search=irene+legs');

            $response->assertStatus(200);
            $viewAlbums = $response->viewData('albums');
            $this->assertCount(1, $viewAlbums);
            $this->assertEquals('alb-1', array_values($viewAlbums)[0]['id']);

            // 2. Multi-term search: '2026 femjoy'
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos?search=2026+femjoy');

            $response->assertStatus(200);
            $viewAlbums = $response->viewData('albums');
            $this->assertCount(1, $viewAlbums);
            $this->assertEquals('alb-2', array_values($viewAlbums)[0]['id']);

            // 3. Quoted search with space: '"my sexy legs"'
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos?search=' . urlencode('"my sexy legs"'));

            $response->assertStatus(200);
            $viewAlbums = $response->viewData('albums');
            $this->assertCount(1, $viewAlbums);
            $this->assertEquals('alb-1', array_values($viewAlbums)[0]['id']);

            // 4. Accent-insensitive: 'russia' matches 'Rússia'
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos?search=russia');

            $response->assertStatus(200);
            $viewAlbums = $response->viewData('albums');
            $this->assertCount(1, $viewAlbums);
            $this->assertEquals('alb-3', array_values($viewAlbums)[0]['id']);

            // 5. Prefix search: 'site:watch4beauty loiro'
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos?search=' . urlencode('site:watch4beauty loiro'));

            $response->assertStatus(200);
            $viewAlbums = $response->viewData('albums');
            $this->assertCount(1, $viewAlbums);
            $this->assertEquals('alb-1', array_values($viewAlbums)[0]['id']);

            // 6. Exclusion search: '2026 -legs' (should match alb-2 and alb-3, but NOT alb-1)
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos?search=' . urlencode('2026 -legs'));

            $response->assertStatus(200);
            $viewAlbums = $response->viewData('albums');
            $this->assertCount(2, $viewAlbums);
            $ids = array_column(array_values($viewAlbums), 'id');
            $this->assertContains('alb-2', $ids);
            $this->assertContains('alb-3', $ids);
            $this->assertNotContains('alb-1', $ids);

            // 7. Combined search + dropdown filters: site=Watch4Beauty & hair=Loiro & search=legs
            $response = $this->actingAs($user)
                ->withSession(['photos_authenticated' => true])
                ->get('/photos?site=Watch4Beauty&hair=Loiro&search=legs');

            $response->assertStatus(200);
            $viewAlbums = $response->viewData('albums');
            $this->assertCount(1, $viewAlbums);
            $this->assertEquals('alb-1', array_values($viewAlbums)[0]['id']);

            // Verify active filter chips are populated
            $chips = $response->viewData('activeFilterChips');
            $this->assertNotEmpty($chips);
            $chipTypes = array_column($chips, 'type');
            $this->assertContains('search', $chipTypes);
            $this->assertContains('site', $chipTypes);
            $this->assertContains('hair', $chipTypes);
        } finally {
            config(['photos.cache_path' => $oldCachePath]);
            @unlink($testCachePath);
        }
    }
}