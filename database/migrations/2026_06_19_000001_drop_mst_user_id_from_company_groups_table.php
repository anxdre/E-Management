<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_company_groups', function (Blueprint $table) {
            try {
                $table->dropForeign('company_groups_user_id_foreign');
            } catch (\Exception) {
                // FK already dropped
            }
            $table->dropColumn('mst_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('mst_company_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('mst_user_id')->nullable()->after('id');
            $table->foreign('mst_user_id', 'company_groups_user_id_foreign')
                ->references('id')
                ->on('mst_users')
                ->onDelete('cascade');
        });
    }
};
