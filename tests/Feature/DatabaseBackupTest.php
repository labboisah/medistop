<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_database_backup_and_falls_back_to_downloads_when_no_drive_is_detected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.database.backup'));

        $response->assertRedirect();
        $this->assertTrue(session('success') !== null || session('warning') !== null);

        $message = session('success') ?? session('warning') ?? '';
        $this->assertStringContainsString('Downloads', $message);
    }
}
