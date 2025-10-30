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
        Schema::create('salary_receipt_dg_tmp', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('user_id');
            $table->integer('work_hour');
            $table->decimal('total_salary')->default(0);
            $table->dateTime('start_date');
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->decimal('total_tax')->nullable()->default(0);
            $table->decimal('salary_after_tax')->default(0);
            $table->integer('total_presence_record')->nullable();
            $table->dateTime('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_receipt_dg_tmp');
    }
};
