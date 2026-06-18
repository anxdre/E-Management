<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\UserManagement\User;
use App\Models\UserManagement\UserDetail;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::create([
            'email' => 'admin@admin.com',
            'password' => bcrypt('Password123'),
            'type' => 'superadmin',
            'email_verified_at' => now(),
        ]);

        CompanyProfile::create([
            'mst_user_id' => $superadmin->id,
            'company_name' => 'Admin Utama',
            'company_phone' => '02112345678',
            'company_address' => 'Jl. Merdeka No. 1, Jakarta Pusat, 10110',
            'company_email' => 'admin@admin.com',
        ]);

        $company = User::create([
            'email' => 'company@admin.com',
            'password' => bcrypt('Password123'),
            'type' => 'company',
            'email_verified_at' => now(),
        ]);

        CompanyProfile::create([
            'mst_user_id' => $company->id,
            'company_name' => 'PT. Maju Jaya Sejahtera',
            'company_phone' => '02155512345',
            'company_address' => 'Jl. Jenderal Sudirman No. 123, Jakarta Pusat, 10220',
            'company_email' => 'company@admin.com',
            'npwp' => '01.234.567.8-901.000',
        ]);

        $employeeData = [
            ['fullname' => 'Rudi Hartono',          'phone' => '081234567890', 'address' => 'Jl. Kebon Jeruk No. 10, Jakarta Barat',                 'email' => 'rudi@email.com'],
            ['fullname' => 'Agus Wijaya',            'phone' => '081234567891', 'address' => 'Jl. Mangga Dua No. 5, Jakarta Utara',                  'email' => 'agus@email.com'],
            ['fullname' => 'Hendra Gunawan',         'phone' => '081234567892', 'address' => 'Jl. Cempaka No. 8, Jakarta Selatan',                   'email' => 'hendra@email.com'],
            ['fullname' => 'Dimas Ardiansyah',       'phone' => '081234567893', 'address' => 'Perumahan Pondok Indah Blok A3, Jakarta Selatan',       'email' => 'dimas@email.com'],
            ['fullname' => 'Siti Nurhaliza',         'phone' => '081234567894', 'address' => 'Jl. Melati No. 3, Bandung, Jawa Barat',                'email' => 'siti@email.com'],
            ['fullname' => 'Dewi Sartika',           'phone' => '081234567895', 'address' => 'Jl. Mawar No. 7, Jakarta Selatan',                     'email' => 'dewi@email.com'],
            ['fullname' => 'Rina Marlina',           'phone' => '081234567896', 'address' => 'Jl. Kamboja No. 15, Depok, Jawa Barat',                'email' => 'rina@email.com'],
            ['fullname' => 'Maya Anggraini',         'phone' => '081234567897', 'address' => 'Jl. Flamboyan No. 22, Bekasi, Jawa Barat',             'email' => 'maya@email.com'],
            ['fullname' => 'Indah Permata Sari',     'phone' => '081234567898', 'address' => 'Jl. Dahlia No. 9, Tangerang, Banten',                  'email' => 'indah@email.com'],
            ['fullname' => 'Budi Santoso',           'phone' => '081234567899', 'address' => 'Jl. Kenanga No. 4, Jakarta Pusat',                     'email' => 'budi@email.com'],
            ['fullname' => 'Andi Pratama',           'phone' => '081234567800', 'address' => 'Jl. Teratai No. 18, Jakarta Barat',                    'email' => 'andi@email.com'],
            ['fullname' => 'Fitriani Ramadhani',     'phone' => '081234567801', 'address' => 'Perumahan Bogor Indah Blok C5, Bogor, Jawa Barat',      'email' => 'fitri@email.com'],
        ];

        foreach ($employeeData as $data) {
            $detail = UserDetail::create([
                'fullname' => $data['fullname'],
                'phone' => $data['phone'],
                'address' => $data['address'],
            ]);

            User::create([
                'email' => $data['email'],
                'password' => bcrypt('Password123'),
                'type' => 'employee',
                'mst_user_detail_id' => $detail->id,
                'email_verified_at' => now(),
            ]);
        }
    }
}
