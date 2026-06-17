<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_receipt', function (Blueprint $table) {
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });

        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->index('status');
            $table->index('approved_date');
        });
    }

    public function down(): void
    {
        Schema::table('salary_receipt', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['start_date', 'end_date']);
        });

        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['approved_date']);
        });
    }
};
