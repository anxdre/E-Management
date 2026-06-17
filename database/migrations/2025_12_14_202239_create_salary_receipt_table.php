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
        Schema::create('salary_receipt', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('work_hour');
            $table->decimal('total_salary',15)->default(0);
            $table->dateTime('start_date');
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->decimal('total_tax',15)->nullable()->default(0);
            $table->decimal('salary_after_tax',15)->default(0);
            $table->integer('total_presence_record')->nullable();
            $table->dateTime('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_receipt');
    }
};
