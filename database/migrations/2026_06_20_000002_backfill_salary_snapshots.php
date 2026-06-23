<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('trx_employee_requested_salary')
            ->whereNull('quantity_snapshot')
            ->update(['quantity_snapshot' => DB::raw('quantity')]);

        DB::table('trx_employee_requested_salary AS ers')
            ->join('mst_company_salary AS cs', 'ers.mst_company_salary_id', '=', 'cs.id')
            ->whereNull('ers.salary_snapshot')
            ->update(['ers.salary_snapshot' => DB::raw('cs.salary')]);

        DB::table('trx_salary_receipt_item AS sri')
            ->join('mst_company_salary AS cs', 'sri.mst_company_salary_id', '=', 'cs.id')
            ->whereNull('sri.salary_name_snapshot')
            ->update([
                'sri.salary_name_snapshot' => DB::raw('cs.name'),
                'sri.salary_rate_snapshot' => DB::raw('cs.salary'),
            ]);
    }

    public function down(): void
    {
        // Irreversible — snapshot values cannot be un-snapped
    }
};
