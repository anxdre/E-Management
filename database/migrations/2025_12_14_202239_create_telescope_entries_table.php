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
        Schema::create('telescope_entries', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('sequence');
            $table->string('uuid')->unique();
            $table->string('batch_id')->index();
            $table->string('family_hash')->nullable()->index();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type');
            $table->text('content');
            $table->dateTime('created_at')->nullable()->index();

            $table->index(['type', 'should_display_on_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telescope_entries');
    }
};
