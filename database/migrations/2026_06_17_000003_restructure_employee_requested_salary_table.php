<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->dropForeign('employee_requested_salary_user_id_foreign');
            $table->dropForeign('employee_requested_salary_company_salary_id_foreign');
        });

        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'company_salary_id']);
        });

        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_salary_id')->after('id');
            $table->integer('quantity')->default(1)->after('employee_salary_id');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_date');
            $table->boolean('is_realized')->default(false)->after('approved_by');

            $table->foreign('employee_salary_id')->references('id')->on('employee_salary')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->dropForeign(['employee_salary_id']);
            $table->dropForeign(['approved_by']);
        });

        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->dropColumn(['employee_salary_id', 'quantity', 'approved_by', 'is_realized']);
        });

        Schema::table('employee_requested_salary', function (Blueprint $table) {
            $table->unsignedBigInteger('company_salary_id')->after('id');
            $table->unsignedBigInteger('user_id')->after('company_salary_id');

            $table->foreign(['user_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['company_salary_id'])->references(['id'])->on('company_salary')->onUpdate('no action')->onDelete('cascade');
        });
    }
};
