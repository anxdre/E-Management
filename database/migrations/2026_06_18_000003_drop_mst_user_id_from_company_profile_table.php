<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_company_profile', function (Blueprint $table) {
            try {
                $table->dropForeign(['mst_user_id']);
            } catch (\Exception) {
                // FK doesn't exist
            }
            $table->dropColumn('mst_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('mst_company_profile', function (Blueprint $table) {
            $table->unsignedBigInteger('mst_user_id')->nullable()->after('id');
            $table->foreign('mst_user_id')
                ->references('id')
                ->on('mst_users')
                ->onDelete('set null');
        });
    }
};
