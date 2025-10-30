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
        Schema::create('salary_receipt_item', function (Blueprint $table) {
            $table->foreignId('company_salary_id')->constrained('company_salary')->cascadeOnDelete();
            $table->foreignId('salary_receipt_id')->constrained('salary_receipt')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('total_value',15,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_receipt_item');
    }
};
