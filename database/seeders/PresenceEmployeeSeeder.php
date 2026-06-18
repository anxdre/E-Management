<?php

namespace Database\Seeders;

use App\Models\PresenceManagement\PresenceLocation;
use App\Models\UserManagement\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PresenceEmployeeSeeder extends Seeder
{
    private array $locationRules = [
        'rudi@email.com'   => [80 => 'Kantor Pusat Jakarta', 90 => 'Cabang Bandung', 100 => 'Showroom Jakarta'],
        'agus@email.com'   => [80 => 'Kantor Pusat Jakarta', 100 => 'Showroom Jakarta'],
        'hendra@email.com' => [60 => 'Kantor Pusat Jakarta', 100 => 'Gudang Surabaya'],
        'dimas@email.com'  => [50 => 'Kantor Pusat Jakarta', 100 => 'Gudang Surabaya'],
        'siti@email.com'   => [20 => 'Kantor Pusat Jakarta', 90 => 'Cabang Bandung', 100 => 'Showroom Jakarta'],
        'dewi@email.com'   => [90 => 'Kantor Pusat Jakarta', 100 => 'Cabang Bandung'],
        'rina@email.com'   => [60 => 'Showroom Jakarta', 100 => 'Kantor Pusat Jakarta'],
        'maya@email.com'   => [100 => 'Kantor Pusat Jakarta'],
        'indah@email.com'  => [80 => 'Kantor Pusat Jakarta', 100 => 'Cabang Bandung'],
        'budi@email.com'   => [80 => 'Kantor Pusat Jakarta', 100 => 'Gudang Surabaya'],
        'andi@email.com'   => [90 => 'Kantor Pusat Jakarta', 100 => 'Showroom Jakarta'],
        'fitri@email.com'  => [80 => 'Kantor Pusat Jakarta', 100 => 'Cabang Medan'],
    ];

    public function run(): void
    {
        $employees = User::where('type', 'employee')->get()->keyBy('email');
        $locations = PresenceLocation::all()->keyBy('name');

        $date = Carbon::parse('2026-04-01');
        $end = Carbon::parse('2026-06-30');

        while ($date <= $end) {
            if ($date->isWeekend()) {
                $date->addDay();
                continue;
            }

            foreach ($employees as $employee) {
                $roll = mt_rand(1, 100);

                // 2% absent — skip entirely
                if ($roll <= 2) continue;

                $location = $this->pickLocation($employee->email, $locations);
                $scenario = $this->classifyScenario($roll);

                $this->createPresenceRecord($employee, $location, $date, $scenario);
            }

            $date->addDay();
        }
    }

    private function classifyScenario(int $roll): string
    {
        return match (true) {
            $roll <= 2   => 'absent',
            $roll <= 7   => 'out_of_radius',
            $roll <= 17  => 'late_checkin',
            $roll <= 22  => 'early_checkout',
            $roll <= 30  => 'overtime',
            $roll <= 35  => 'no_checkout',
            default      => 'normal',
        };
    }

    private function pickLocation(string $email, \Illuminate\Support\Collection $locations): PresenceLocation
    {
        $rules = $this->locationRules[$email] ?? [100 => 'Kantor Pusat Jakarta'];
        $roll = mt_rand(1, 100);

        foreach ($rules as $threshold => $name) {
            if ($roll <= $threshold) {
                return $locations->get($name) ?? $locations->first();
            }
        }

        return $locations->first();
    }

    private function createPresenceRecord(User $employee, PresenceLocation $location, Carbon $date, string $scenario): void
    {
        $maxHour = $location->max_hour ? Carbon::parse($location->max_hour) : null;
        $minHour = $location->min_hour ? Carbon::parse($location->min_hour) : null;
        $startHour = $location->start_hour ? Carbon::parse($location->start_hour) : null;
        $endHour = $location->end_hour ? Carbon::parse($location->end_hour) : null;

        $isOutOfRadius = $scenario === 'out_of_radius';
        $lat = $location->latitude + ($isOutOfRadius ? mt_rand(-300, 300) / 10000 : mt_rand(-5, 5) / 10000);
        $lng = $location->longitude + ($isOutOfRadius ? mt_rand(-300, 300) / 10000 : mt_rand(-5, 5) / 10000);

        $checkIn = $this->generateCheckIn($date, $scenario, $startHour, $endHour, $maxHour);
        $note = $isOutOfRadius ? 'Check in di luar radius yang ditentukan' : '';

        if ($scenario === 'no_checkout') {
            DB::table('trx_presence_employees')->insert([
                'mst_user_id' => $employee->id,
                'mst_presence_location_id' => $location->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'time_in' => $checkIn,
                'time_out' => null,
                'extended_time' => null,
                'note' => 'Check in tanpa check out',
                'status_by_admin' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        $checkOut = $this->generateCheckOut($date, $scenario, $minHour, $maxHour);
        $extendedTime = null;
        $status = 'approved';

        if ($scenario === 'late_checkin') {
            $note = 'Terlambat check in';
            $status = 'pending';
        }

        if ($scenario === 'overtime' && $maxHour) {
            $diffSeconds = $checkOut->diffInSeconds($maxHour);
            $extendedTime = Carbon::now()->startOfDay()->addSeconds($diffSeconds);
            $note = 'Lembur - check out melewati batas jam maksimal';
            $status = 'pending';
        }

        if ($scenario === 'early_checkout' && $minHour) {
            $diffSeconds = $maxHour ? $maxHour->diffInSeconds($checkOut) : 3600;
            $extendedTime = Carbon::now()->startOfDay()->addSeconds($diffSeconds);
            $note = 'Check out lebih awal';
            $status = 'pending';
        }

        DB::table('trx_presence_employees')->insert([
            'mst_user_id' => $employee->id,
            'mst_presence_location_id' => $location->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'time_in' => $checkIn,
            'time_out' => $checkOut,
            'extended_time' => $extendedTime,
            'note' => $note ?: null,
            'status_by_admin' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateCheckIn(Carbon $date, string $scenario, ?Carbon $startHour, ?Carbon $endHour, ?Carbon $maxHour): Carbon
    {
        return match ($scenario) {
            'late_checkin' => $date->copy()->setTime(
                $endHour ? $endHour->hour + mt_rand(0, 1) : mt_rand(9, 10),
                mt_rand(0, 59),
                mt_rand(0, 59)
            ),
            'early_checkout', 'overtime', 'normal', 'out_of_radius', 'no_checkout' => $date->copy()->setTime(
                $startHour ? $startHour->hour + mt_rand(0, 1) : mt_rand(7, 8),
                mt_rand(0, 59),
                mt_rand(0, 59)
            ),
            default => $date->copy()->setTime(mt_rand(7, 8), mt_rand(0, 59), mt_rand(0, 59)),
        };
    }

    private function generateCheckOut(Carbon $date, string $scenario, ?Carbon $minHour, ?Carbon $maxHour): Carbon
    {
        return match ($scenario) {
            'overtime' => $date->copy()->setTime(
                $maxHour ? $maxHour->hour + mt_rand(0, 2) : mt_rand(18, 20),
                mt_rand(0, 59),
                mt_rand(0, 59)
            ),
            'early_checkout' => $date->copy()->setTime(
                $minHour ? max(0, $minHour->hour - mt_rand(1, 2)) : mt_rand(14, 15),
                mt_rand(0, 59),
                mt_rand(0, 59)
            ),
            'late_checkin', 'normal', 'out_of_radius', 'no_checkout' => $date->copy()->setTime(
                $minHour ? $minHour->hour + mt_rand(0, 2) : mt_rand(16, 18),
                mt_rand(0, 59),
                mt_rand(0, 59)
            ),
            default => $date->copy()->setTime(mt_rand(16, 18), mt_rand(0, 59), mt_rand(0, 59)),
        };
    }
}
