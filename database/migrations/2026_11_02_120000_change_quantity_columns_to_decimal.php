<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('balance_before', 12, 4)->nullable()->change();
            $table->decimal('balance_after', 12, 4)->nullable()->change();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->decimal('stock', 12, 4)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('balance_before')->nullable()->change();
            $table->integer('balance_after')->nullable()->change();
        });

        Schema::table('stock_requests', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });
    }
};
