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
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('presence_location_id')->constrained('presence_locations');
            $table->foreignId('presence_verification_id')->constrained('presence_verifications');
            $table->double('latitude');
            $table->double('longitude');
            $table->dateTime('time_in');
            $table->dateTime('time_out');
            $table->dateTime('extended_time')->nullable();
            $table->text('note')->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status_by_admin',['pending','approved','rejected'])->default('pending');
            $table->timestamps();
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
