<?php

namespace Database\Seeders;

use App\Models\UserManagement\CompanyGroup;
use App\Models\UserManagement\User;
use Illuminate\Database\Seeder;

class CompanyGroupSeeder extends Seeder
{
    public function run(): void
    {
        $company = User::where('email', 'company@admin.com')->first();

        $groups = [
            'Engineering'      => ['rudi@email.com', 'agus@email.com', 'hendra@email.com', 'dimas@email.com'],
            'Marketing'        => ['siti@email.com', 'dewi@email.com', 'rina@email.com'],
            'Human Resources'  => ['maya@email.com', 'indah@email.com'],
            'Finance'          => ['budi@email.com', 'andi@email.com', 'fitri@email.com'],
        ];

        foreach ($groups as $name => $emails) {
            $group = CompanyGroup::create([
                'mst_user_id' => $company->id,
                'name' => $name,
            ]);

            $userIds = User::whereIn('email', $emails)->pluck('id');
            $group->employee()->attach($userIds);
        }
    }
}
