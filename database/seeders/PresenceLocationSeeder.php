<?php

namespace Database\Seeders;

use App\Models\PresenceManagement\PresenceLocation;
use App\Models\PresenceManagement\PresenceVerification;
use App\Models\UserManagement\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PresenceLocationSeeder extends Seeder
{
    public function run(): void
    {
        $company = User::where('email', 'company@admin.com')->first();

        $locations = [
            [
                'name' => 'Kantor Pusat Jakarta',
                'latitude' => -6.2146,
                'longitude' => 106.8451,
                'tolerance' => 100,
                'start_hour' => '07:00:00',
                'end_hour' => '09:00:00',
                'min_hour' => '16:00:00',
                'max_hour' => '18:00:00',
            ],
            [
                'name' => 'Cabang Bandung',
                'latitude' => -6.9039,
                'longitude' => 107.6187,
                'tolerance' => 150,
                'start_hour' => '07:30:00',
                'end_hour' => '09:30:00',
                'min_hour' => '16:00:00',
                'max_hour' => '17:30:00',
            ],
            [
                'name' => 'Gudang Surabaya',
                'latitude' => -7.2575,
                'longitude' => 112.7521,
                'tolerance' => 200,
                'start_hour' => '06:00:00',
                'end_hour' => '08:00:00',
                'min_hour' => '15:00:00',
                'max_hour' => '17:00:00',
            ],
            [
                'name' => 'Showroom Jakarta',
                'latitude' => -6.2254,
                'longitude' => 106.8072,
                'tolerance' => 100,
                'start_hour' => '08:00:00',
                'end_hour' => '10:00:00',
                'min_hour' => '17:00:00',
                'max_hour' => '20:00:00',
            ],
            [
                'name' => 'Cabang Medan',
                'latitude' => 3.5952,
                'longitude' => 98.6722,
                'tolerance' => 100,
                'start_hour' => '07:00:00',
                'end_hour' => '09:00:00',
                'min_hour' => '16:00:00',
                'max_hour' => '18:00:00',
            ],
        ];

        $createdLocations = [];
        foreach ($locations as $data) {
            $loc = PresenceLocation::create(array_merge($data, ['mst_user_id' => $company->id]));
            $createdLocations[] = $loc;
        }

        $now = CarbonImmutable::now('Asia/Jakarta');
        $date = $now->format('d');
        $second = $now->format('s');

        foreach ($createdLocations as $location) {
            $randPrefix = rand(10, 99);
            PresenceVerification::create([
                'mst_presence_location_id' => $location->id,
                'verification_hash' => "$randPrefix$date$second",
            ]);
        }
    }
}
