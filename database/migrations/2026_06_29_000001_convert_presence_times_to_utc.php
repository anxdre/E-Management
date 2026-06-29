<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing WIB (UTC+7) timestamps to UTC in trx_presence_employees
        // time_in, time_out had been stored in WIB because APP_TIMEZONE=Asia/Jakarta
        // extended_time is a duration, not a timestamp — do NOT convert
        DB::statement("
            UPDATE trx_presence_employees
            SET
                time_in     = DATE_SUB(time_in, INTERVAL 7 HOUR),
                time_out    = DATE_SUB(time_out, INTERVAL 7 HOUR),
                created_at  = DATE_SUB(created_at, INTERVAL 7 HOUR),
                updated_at  = DATE_SUB(updated_at, INTERVAL 7 HOUR)
        ");
    }

    public function down(): void
    {
        // Reverse: add 7 hours back (UTC back to WIB)
        DB::statement("
            UPDATE trx_presence_employees
            SET
                time_in     = DATE_ADD(time_in, INTERVAL 7 HOUR),
                time_out    = DATE_ADD(time_out, INTERVAL 7 HOUR),
                created_at  = DATE_ADD(created_at, INTERVAL 7 HOUR),
                updated_at  = DATE_ADD(updated_at, INTERVAL 7 HOUR)
        ");
    }
};
