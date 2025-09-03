<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KidRequestImageUploadTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_kid_can_create_request_with_image()
    {
        Storage::fake('public');

        $kid = User::factory()->create(['type' => 'child']);

        $this->actingAs($kid, 'sanctum');

        $image = UploadedFile::fake()->image('toy.png', 300, 300);

        $response = $this->post('/api/kid/requests', [
            'title' => 'Xin mua đồ chơi',
            'description' => 'Con muốn mua một món đồ chơi mới',
            'type' => 'toy',
            'image' => $image,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'id','child_id','title','description','type','status','image','image_url','created_at','updated_at'
        ]);

        $this->assertNotNull($response->json('image'));
        Storage::disk('public')->assertExists($response->json('image'));
    }

    /** @test */
    public function image_is_optional()
    {
        $kid = User::factory()->create(['type' => 'child']);
        $this->actingAs($kid, 'sanctum');

        $response = $this->post('/api/kid/requests', [
            'title' => 'Đi công viên',
            'description' => 'Con muốn đi công viên cuối tuần',
            'type' => 'playground',
        ]);

        $response->assertCreated();
        $this->assertNull($response->json('image'));
    }

    /** @test */
    public function rejects_invalid_image_type()
    {
        Storage::fake('public');

        $kid = User::factory()->create(['type' => 'child']);
        $this->actingAs($kid, 'sanctum');

        $file = UploadedFile::fake()->create('document.pdf', 10, 'application/pdf');

        $response = $this->post('/api/kid/requests', [
            'title' => 'Mua sách',
            'description' => 'Con muốn mua sách',
            'type' => 'toy',
            'image' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }
}

