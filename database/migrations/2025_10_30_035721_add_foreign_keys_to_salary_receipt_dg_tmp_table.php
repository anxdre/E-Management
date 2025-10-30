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
        Schema::table('salary_receipt_dg_tmp', function (Blueprint $table) {
            $table->foreign(['user_id'], null)->references([''])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_receipt_dg_tmp', function (Blueprint $table) {
            $table->dropForeign();
        });
    }
};
