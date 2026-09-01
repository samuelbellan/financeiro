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

        $response = $this->actingAs($user)
            ->withSession(['photos_authenticated' => true])
            ->get('/photos');

        $response->assertStatus(200);
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
}
