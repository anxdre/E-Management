<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_users', function (Blueprint $table) {
            if (Schema::hasColumn('mst_users', 'mst_company_id')) {
                $table->dropColumn('mst_company_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mst_users', function (Blueprint $table) {
            $table->unsignedBigInteger('mst_company_id')->nullable()->after('mst_user_detail_id');

            $table->foreign('mst_company_id')
                ->references('id')
                ->on('mst_users')
                ->onDelete('set null');
        });
    }
};
