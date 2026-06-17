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
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('company_salary_id');
            $table->unsignedBigInteger('salary_receipt_id');
            $table->integer('quantity');
            $table->decimal('total_value',15);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
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
