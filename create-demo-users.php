<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$users = [
    ['name' => 'Admin Utama', 'email' => 'admin@example.com', 'role' => 'admin'],
    ['name' => 'HR User', 'email' => 'hr@example.com', 'role' => 'hr'],
    ['name' => 'Gudang User', 'email' => 'gudang@example.com', 'role' => 'gudang'],
];

foreach ($users as $data) {
    $user = User::updateOrCreate(
        ['email' => $data['email']],
        ['name' => $data['name'], 'password' => Hash::make('password123'), 'role' => $data['role']]
    );
    echo $user->email . ' => ' . $user->role . PHP_EOL;
}
