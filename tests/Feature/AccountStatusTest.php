<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::create([
            'name' => 'User Nonaktif',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'role' => 'gudang',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrors(['email' => 'Email atau password yang Anda masukkan tidak sesuai. Jika membutuhkan bantuan, hubungi administrator.']);
        $this->assertGuest();
    }
}
