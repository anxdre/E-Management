<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_users', function (Blueprint $table) {
            $table->foreignId('mst_user_detail_id')->nullable()->change();
        });

        DB::beginTransaction();
        try {
            $companyUsers = DB::table('mst_users')
                ->whereIn('type', ['superadmin', 'company'])
                ->whereNotNull('mst_user_detail_id')
                ->get();

            foreach ($companyUsers as $user) {
                $detail = DB::table('mst_user_details')->find($user->mst_user_detail_id);
                if (!$detail) continue;

                DB::table('mst_company_profile')->insert([
                    'mst_user_id' => $user->id,
                    'company_name' => $detail->fullname,
                    'company_phone' => $detail->phone,
                    'company_address' => $detail->address,
                    'company_logo' => $detail->picture_profile,
                    'company_email' => $user->email,
                ]);

                DB::table('mst_users')
                    ->where('id', $user->id)
                    ->update(['mst_user_detail_id' => null]);
            }

            $orphanedIds = DB::table('mst_user_details')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('mst_users')
                        ->whereColumn('mst_users.mst_user_detail_id', 'mst_user_details.id');
                })
                ->pluck('id');

            DB::table('mst_user_details')->whereIn('id', $orphanedIds)->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function down(): void
    {
        DB::beginTransaction();
        try {
            $profiles = DB::table('mst_company_profile')->get();

            foreach ($profiles as $profile) {
                $detailId = DB::table('mst_user_details')->insertGetId([
                    'fullname' => $profile->company_name,
                    'phone' => $profile->company_phone ?? '000000000',
                    'address' => $profile->company_address ?? '',
                    'picture_profile' => $profile->company_logo,
                ]);

                DB::table('mst_users')
                    ->where('id', $profile->mst_user_id)
                    ->update(['mst_user_detail_id' => $detailId]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
};
