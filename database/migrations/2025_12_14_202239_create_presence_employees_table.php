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
        Schema::create('presence_employees', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('presence_location_id');
            $table->double('latitude');
            $table->double('longitude');
            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();
            $table->dateTime('extended_time')->nullable();
            $table->text('note')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status_by_admin', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presence_employees');
    }
};
