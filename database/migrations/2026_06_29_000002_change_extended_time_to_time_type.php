<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trx_presence_employees', function (Blueprint $table) {
            $table->time('extended_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trx_presence_employees', function (Blueprint $table) {
            $table->dateTime('extended_time')->nullable()->change();
        });
    }
};
