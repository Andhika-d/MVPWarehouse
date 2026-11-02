<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@mvpwarehouse.com')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function ($user): void {
                $email = preg_replace('/@mvpwarehouse\.com$/i', '@thiwarehouse.com', $user->email);

                DB::table('users')->where('id', $user->id)->update(['email' => $email]);
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@thiwarehouse.com')
            ->orderBy('id')
            ->get(['id', 'email'])
            ->each(function ($user): void {
                $email = preg_replace('/@thiwarehouse\.com$/i', '@mvpwarehouse.com', $user->email);

                DB::table('users')->where('id', $user->id)->update(['email' => $email]);
            });
    }
};
