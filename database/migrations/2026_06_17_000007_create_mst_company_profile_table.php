<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old mst_company_profile (unused, wrong schema)
        Schema::dropIfExists('mst_company_profile');

        Schema::create('mst_company_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mst_user_id')->unique()->constrained('mst_users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('company_phone')->nullable();
            $table->string('company_address')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('company_email')->nullable();
            $table->string('npwp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_company_profile');
    }
};
