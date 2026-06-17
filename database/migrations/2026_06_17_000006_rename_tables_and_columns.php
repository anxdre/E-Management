<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === STEP 1: Rename columns ===

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'user_detail_id')) $table->renameColumn('user_detail_id', 'mst_user_detail_id');
            if (Schema::hasColumn('users', 'company_id')) $table->renameColumn('company_id', 'mst_company_id');
        });

        if (Schema::hasTable('company_groups')) {
            Schema::table('company_groups', function (Blueprint $table) {
                if (Schema::hasColumn('company_groups', 'user_id')) $table->renameColumn('user_id', 'mst_user_id');
            });
        }

        if (Schema::hasTable('group_has_users')) {
            Schema::table('group_has_users', function (Blueprint $table) {
                if (Schema::hasColumn('group_has_users', 'user_id')) $table->renameColumn('user_id', 'mst_user_id');
                if (Schema::hasColumn('group_has_users', 'group_id')) $table->renameColumn('group_id', 'mst_company_group_id');
            });
        }

        if (Schema::hasTable('presence_locations')) {
            Schema::table('presence_locations', function (Blueprint $table) {
                if (Schema::hasColumn('presence_locations', 'user_id')) $table->renameColumn('user_id', 'mst_user_id');
            });
        }

        if (Schema::hasTable('presence_employees')) {
            Schema::table('presence_employees', function (Blueprint $table) {
                if (Schema::hasColumn('presence_employees', 'user_id')) $table->renameColumn('user_id', 'mst_user_id');
                if (Schema::hasColumn('presence_employees', 'presence_location_id')) $table->renameColumn('presence_location_id', 'mst_presence_location_id');
                if (Schema::hasColumn('presence_employees', 'presence_verification_id')) $table->renameColumn('presence_verification_id', 'trx_presence_verification_id');
            });
        }

        if (Schema::hasTable('presence_verifications')) {
            Schema::table('presence_verifications', function (Blueprint $table) {
                if (Schema::hasColumn('presence_verifications', 'presence_location_id')) $table->renameColumn('presence_location_id', 'mst_presence_location_id');
            });
        }

        if (Schema::hasTable('employee_salary')) {
            Schema::table('employee_salary', function (Blueprint $table) {
                if (Schema::hasColumn('employee_salary', 'user_id')) $table->renameColumn('user_id', 'mst_user_id');
                if (Schema::hasColumn('employee_salary', 'company_salary_id')) $table->renameColumn('company_salary_id', 'mst_company_salary_id');
            });
        }

        if (Schema::hasTable('employee_requested_salary')) {
            Schema::table('employee_requested_salary', function (Blueprint $table) {
                if (Schema::hasColumn('employee_requested_salary', 'employee_salary_id')) $table->renameColumn('employee_salary_id', 'pivot_employee_salary_id');
                if (Schema::hasColumn('employee_requested_salary', 'approved_by')) $table->renameColumn('approved_by', 'mst_approved_by');
            });
        }

        if (Schema::hasTable('salary_receipt')) {
            Schema::table('salary_receipt', function (Blueprint $table) {
                if (Schema::hasColumn('salary_receipt', 'user_id')) $table->renameColumn('user_id', 'mst_user_id');
            });
        }

        if (Schema::hasTable('salary_receipt_item')) {
            Schema::table('salary_receipt_item', function (Blueprint $table) {
                if (Schema::hasColumn('salary_receipt_item', 'salary_receipt_id')) $table->renameColumn('salary_receipt_id', 'trx_salary_receipt_id');
                if (Schema::hasColumn('salary_receipt_item', 'company_salary_id')) $table->renameColumn('company_salary_id', 'mst_company_salary_id');
                if (Schema::hasColumn('salary_receipt_item', 'employee_requested_salary_id')) $table->renameColumn('employee_requested_salary_id', 'trx_employee_requested_salary_id');
            });
        }

        if (Schema::hasTable('role_permission')) {
            Schema::table('role_permission', function (Blueprint $table) {
                if (Schema::hasColumn('role_permission', 'app_menus_id')) $table->renameColumn('app_menus_id', 'mst_app_menus_id');
                if (Schema::hasColumn('role_permission', 'company_groups_id')) $table->renameColumn('company_groups_id', 'mst_company_groups_id');
            });
        }

        // === STEP 2: Rename tables ===

        if (Schema::hasTable('users'))                  Schema::rename('users', 'mst_users');
        if (Schema::hasTable('user_details'))            Schema::rename('user_details', 'mst_user_details');
        if (Schema::hasTable('company_groups'))          Schema::rename('company_groups', 'mst_company_groups');
        if (Schema::hasTable('company_salary'))          Schema::rename('company_salary', 'mst_company_salary');
        if (Schema::hasTable('presence_locations'))      Schema::rename('presence_locations', 'mst_presence_locations');
        if (Schema::hasTable('app_menus'))               Schema::rename('app_menus', 'mst_app_menus');
        if (Schema::hasTable('company_profile'))         Schema::rename('company_profile', 'mst_company_profile');

        if (Schema::hasTable('presence_employees'))          Schema::rename('presence_employees', 'trx_presence_employees');
        if (Schema::hasTable('presence_verifications'))      Schema::rename('presence_verifications', 'trx_presence_verifications');
        if (Schema::hasTable('employee_requested_salary'))   Schema::rename('employee_requested_salary', 'trx_employee_requested_salary');
        if (Schema::hasTable('salary_receipt'))              Schema::rename('salary_receipt', 'trx_salary_receipt');
        if (Schema::hasTable('salary_receipt_item'))         Schema::rename('salary_receipt_item', 'trx_salary_receipt_item');

        if (Schema::hasTable('group_has_users'))   Schema::rename('group_has_users', 'pivot_group_has_users');
        if (Schema::hasTable('employee_salary'))   Schema::rename('employee_salary', 'pivot_employee_salary');
        if (Schema::hasTable('role_permission'))   Schema::rename('role_permission', 'pivot_role_permission');
    }

    public function down(): void
    {
        if (Schema::hasTable('pivot_role_permission'))   Schema::rename('pivot_role_permission', 'role_permission');
        if (Schema::hasTable('pivot_employee_salary'))   Schema::rename('pivot_employee_salary', 'employee_salary');
        if (Schema::hasTable('pivot_group_has_users'))   Schema::rename('pivot_group_has_users', 'group_has_users');

        if (Schema::hasTable('trx_salary_receipt_item'))         Schema::rename('trx_salary_receipt_item', 'salary_receipt_item');
        if (Schema::hasTable('trx_salary_receipt'))              Schema::rename('trx_salary_receipt', 'salary_receipt');
        if (Schema::hasTable('trx_employee_requested_salary'))   Schema::rename('trx_employee_requested_salary', 'employee_requested_salary');
        if (Schema::hasTable('trx_presence_verifications'))      Schema::rename('trx_presence_verifications', 'presence_verifications');
        if (Schema::hasTable('trx_presence_employees'))          Schema::rename('trx_presence_employees', 'presence_employees');

        if (Schema::hasTable('mst_company_profile'))         Schema::rename('mst_company_profile', 'company_profile');
        if (Schema::hasTable('mst_app_menus'))               Schema::rename('mst_app_menus', 'app_menus');
        if (Schema::hasTable('mst_presence_locations'))      Schema::rename('mst_presence_locations', 'presence_locations');
        if (Schema::hasTable('mst_company_salary'))          Schema::rename('mst_company_salary', 'company_salary');
        if (Schema::hasTable('mst_company_groups'))          Schema::rename('mst_company_groups', 'company_groups');
        if (Schema::hasTable('mst_user_details'))            Schema::rename('mst_user_details', 'user_details');
        if (Schema::hasTable('mst_users'))                   Schema::rename('mst_users', 'users');
    }
};
