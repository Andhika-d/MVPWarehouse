<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_email_and_wrong_password_use_same_message(): void
    {
        User::create([
            'name' => 'Login User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'gudang',
            'is_active' => true,
        ]);

        $unknownEmail = $this->from('/login')->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'password123',
        ]);

        $wrongPassword = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $message = 'Email atau password yang Anda masukkan tidak sesuai. Pastikan data sudah benar.';
        $unknownEmail->assertRedirect('/login')->assertSessionHasErrors(['email' => $message]);
        $wrongPassword->assertRedirect('/login')->assertSessionHasErrors(['email' => $message]);
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'email' => 'unknown@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }
}
