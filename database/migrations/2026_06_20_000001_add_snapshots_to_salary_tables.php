<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trx_salary_receipt_item', function (Blueprint $table) {
            $table->string('salary_name_snapshot')->nullable()->after('id');
            $table->decimal('salary_rate_snapshot', 15, 2)->nullable()->after('salary_name_snapshot');
        });

        Schema::table('trx_employee_requested_salary', function (Blueprint $table) {
            $table->integer('quantity_snapshot')->nullable()->after('quantity');
            $table->decimal('salary_snapshot', 15, 2)->nullable()->after('quantity_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('trx_salary_receipt_item', function (Blueprint $table) {
            $table->dropColumn(['salary_name_snapshot', 'salary_rate_snapshot']);
        });

        Schema::table('trx_employee_requested_salary', function (Blueprint $table) {
            $table->dropColumn(['quantity_snapshot', 'salary_snapshot']);
        });
    }
};
