<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salary', function (Blueprint $table) {
            $table->id()->first();
            $table->unique(['company_salary_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_salary', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->dropUnique(['company_salary_id', 'user_id']);
        });
    }
};
