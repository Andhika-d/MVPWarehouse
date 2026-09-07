<?php

namespace Tests\Feature;

use App\Models\HelpGuide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_guides_are_visible_to_matching_role(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);
        HelpGuide::create(['title' => 'Request Barang', 'slug' => 'request-barang', 'category' => 'Gudang', 'audience_role' => 'gudang', 'status' => 'published']);
        HelpGuide::create(['title' => 'Panduan HR', 'slug' => 'panduan-hr', 'category' => 'HR', 'audience_role' => 'hr', 'status' => 'published']);
        HelpGuide::create(['title' => 'Draft Gudang', 'slug' => 'draft-gudang', 'category' => 'Gudang', 'audience_role' => 'gudang', 'status' => 'draft']);

        $this->actingAs($gudang)->get('/bantuan')
            ->assertOk()
            ->assertSee('Request Barang')
            ->assertDontSee('Panduan HR')
            ->assertDontSee('Draft Gudang');
    }

    public function test_admin_can_access_help_guide_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/help-guides')->assertOk();
        $this->actingAs($admin)->get('/admin/help-guides/create')->assertOk();
    }

    public function test_non_admin_cannot_access_help_guide_management(): void
    {
        $gudang = User::factory()->create(['role' => 'gudang']);

        $this->actingAs($gudang)->get('/admin/help-guides')->assertForbidden();
    }
}
