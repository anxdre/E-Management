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
        Schema::table('group_has_users', function (Blueprint $table) {
            $table->foreign(['user_id'], null)->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['group_id'], null)->references(['id'])->on('company_groups')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_has_users', function (Blueprint $table) {
            $table->dropForeign();
            $table->dropForeign();
        });
    }
};
