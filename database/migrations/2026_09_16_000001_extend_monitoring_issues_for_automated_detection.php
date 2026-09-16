<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_issues', function (Blueprint $table) {
            $table->string('source')->nullable()->after('user_id');
            $table->string('rule_key', 80)->default('')->after('category');
            $table->string('subject_type')->nullable()->after('rule_key');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->string('dedupe_key', 190)->nullable()->after('subject_id');
            $table->json('context')->nullable()->after('note');
            $table->timestamp('detected_at')->nullable()->after('context');
            $table->timestamp('last_seen_at')->nullable()->after('detected_at');
            $table->timestamp('resolved_at')->nullable()->after('last_seen_at');
            $table->unsignedInteger('occurrence_count')->default(1)->after('resolved_at');

            $table->unique('dedupe_key');
            $table->index(['status'], 'monitoring_issues_status_index');
            $table->index(['rule_key'], 'monitoring_issues_rule_key_index');
            $table->index(['subject_type', 'subject_id'], 'monitoring_issues_subject_index');
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_issues', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropIndex('monitoring_issues_status_index');
            $table->dropIndex('monitoring_issues_rule_key_index');
            $table->dropIndex('monitoring_issues_subject_index');
            $table->dropColumn([
                'source',
                'rule_key',
                'subject_type',
                'subject_id',
                'dedupe_key',
                'context',
                'detected_at',
                'last_seen_at',
                'resolved_at',
                'occurrence_count',
            ]);
        });
    }
};