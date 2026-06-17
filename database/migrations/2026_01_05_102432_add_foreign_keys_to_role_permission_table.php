<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('role_permission', function (Blueprint $table) {
            $table->foreign(['app_menus_id'], null)->references(['id'])->on('app_menus')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['company_groups_id'], null)->references(['id'])->on('company_groups')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_permission', function (Blueprint $table) {
            $table->dropForeign();
            $table->dropForeign();
        });
    }
};
