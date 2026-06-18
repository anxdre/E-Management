<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_company_profile', function (Blueprint $table) {
            $table->boolean('auto_approve')->default(false)->after('npwp');
            $table->enum('auto_approve_mode', ['realtime', 'cron'])->default('realtime')->after('auto_approve');
            $table->time('auto_approve_batch_hour')->nullable()->default('17:00:00')->after('auto_approve_mode');
            $table->integer('auto_approve_min_duration')->default(60)->after('auto_approve_batch_hour');
            $table->boolean('auto_approve_duplicate_coords')->default(false)->after('auto_approve_min_duration');
        });
    }

    public function down(): void
    {
        Schema::table('mst_company_profile', function (Blueprint $table) {
            $table->dropColumn([
                'auto_approve',
                'auto_approve_mode',
                'auto_approve_batch_hour',
                'auto_approve_min_duration',
                'auto_approve_duplicate_coords',
            ]);
        });
    }
};
