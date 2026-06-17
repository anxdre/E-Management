<?php

namespace Database\Seeders;

use App\Models\UserManagement\User;
use App\Models\UserManagement\UserDetail;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        UserDetail::query()->create([
            'id' => 1,
            'fullname' => 'Administrator',
            'phone' => '000000000',
            'address' => 'Address',
        ]);

        User::query()->create([
            'email' => 'admin@admin.com',
            'password' => bcrypt('Password123'),
            'type' => 'superadmin',
            'email_verified_at' => now(),
            'mst_user_detail_id' => 1,
        ]);
    }
}
