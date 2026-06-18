<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_presence_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('mst_presence_locations', 'mst_user_id')) {
                $table->unsignedBigInteger('mst_user_id')->nullable()->after('id');
                $table->foreign('mst_user_id')->references('id')->on('mst_users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('mst_presence_locations', 'end_hour')) {
                $table->time('end_hour')->nullable()->after('start_hour');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mst_presence_locations', function (Blueprint $table) {
            if (Schema::hasColumn('mst_presence_locations', 'mst_user_id')) {
                $table->dropForeign(['mst_user_id']);
                $table->dropColumn('mst_user_id');
            }
        });
        Schema::table('mst_presence_locations', function (Blueprint $table) {
            if (Schema::hasColumn('mst_presence_locations', 'end_hour')) {
                $table->dropColumn('end_hour');
            }
        });
    }
};
