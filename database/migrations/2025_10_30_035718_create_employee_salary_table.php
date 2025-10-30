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
        Schema::create('employee_salary', function (Blueprint $table) {
            $table->integer('company_salary_id');
            $table->integer('user_id');
            $table->boolean('available_to_request')->default(false);
            $table->boolean('included_at_default')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salary');
    }
};
