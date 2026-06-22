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
        Schema::table('salary_receipt_item', function (Blueprint $table) {
            $table->foreign(['salary_receipt_id'], null)->references(['id'])->on('salary_receipt')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['company_salary_id'], null)->references(['id'])->on('company_salary')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_receipt_item', function (Blueprint $table) {
            $table->dropForeign();
            $table->dropForeign();
        });
    }
};
