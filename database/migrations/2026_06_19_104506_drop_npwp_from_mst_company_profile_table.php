<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_company_profile', function (Blueprint $table) {
            $table->dropColumn('npwp');
        });
    }

    public function down(): void
    {
        Schema::table('mst_company_profile', function (Blueprint $table) {
            $table->string('npwp')->nullable()->after('company_email');
        });
    }
};
