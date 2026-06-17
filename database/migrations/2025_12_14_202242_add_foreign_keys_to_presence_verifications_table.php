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
        Schema::table('presence_verifications', function (Blueprint $table) {
            $table->foreign(['presence_location_id'], null)->references(['id'])->on('presence_locations')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presence_verifications', function (Blueprint $table) {
            $table->dropForeign();
        });
    }
};
