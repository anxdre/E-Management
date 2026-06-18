<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE `trx_employee_requested_salary` DROP FOREIGN KEY `employee_requested_salary_employee_salary_id_foreign`');
        } catch (\Exception) {}

        Schema::table('trx_employee_requested_salary', function (Blueprint $table) {
            $table->dropColumn('pivot_employee_salary_id');

            $table->unsignedBigInteger('mst_user_id')->nullable()->after('id');
            $table->unsignedBigInteger('mst_company_salary_id')->nullable()->after('mst_user_id');

            $table->foreign('mst_user_id')
                ->references('id')->on('mst_users')
                ->onDelete('cascade');

            $table->foreign('mst_company_salary_id')
                ->references('id')->on('mst_company_salary')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('trx_employee_requested_salary', function (Blueprint $table) {
            $table->dropForeign(['mst_user_id']);
            $table->dropForeign(['mst_company_salary_id']);

            $table->dropColumn(['mst_user_id', 'mst_company_salary_id']);

            $table->unsignedBigInteger('pivot_employee_salary_id')->nullable()->after('id');

            $table->foreign('pivot_employee_salary_id', 'employee_requested_salary_employee_salary_id_foreign')
                ->references('id')->on('pivot_employee_salary')
                ->onDelete('cascade');
        });
    }
};
