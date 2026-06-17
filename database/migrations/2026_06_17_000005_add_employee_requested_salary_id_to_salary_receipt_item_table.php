<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_receipt_item', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_requested_salary_id')->nullable()->after('id');
            $table->foreign('employee_requested_salary_id', 'fk_sri_employee_requested_salary')
                ->references('id')->on('employee_requested_salary')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('salary_receipt_item', function (Blueprint $table) {
            $table->dropForeign('fk_sri_employee_requested_salary');
            $table->dropColumn('employee_requested_salary_id');
        });
    }
};
