<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('trx_salary_receipt_item', 'deleted_at')) {
            Schema::table('trx_salary_receipt_item', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('trx_salary_receipt_item', 'deleted_at')) {
            Schema::table('trx_salary_receipt_item', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }
};
