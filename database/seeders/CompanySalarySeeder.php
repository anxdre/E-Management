<?php

namespace Database\Seeders;

use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\EmployeeRequestedSalary;
use App\Models\UserManagement\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySalarySeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['name' => 'Gaji Pokok',             'salary' => 5000000, 'type' => 'fixed',    'calculation_type' => 'add',      'is_tax' => false],
            ['name' => 'Tunjangan Transportasi',  'salary' => 500000,  'type' => 'fixed',    'calculation_type' => 'add',      'is_tax' => false],
            ['name' => 'Tunjangan Makan',         'salary' => 35000,   'type' => 'presence', 'calculation_type' => 'add',      'is_tax' => false],
            ['name' => 'Uang Lembur',             'salary' => 50000,   'type' => 'hourly',   'calculation_type' => 'add',      'is_tax' => false],
            ['name' => 'BPJS Kesehatan',          'salary' => 2,       'type' => 'tax',      'calculation_type' => 'subtract', 'is_tax' => true],
            ['name' => 'PPh 21',                  'salary' => 5,       'type' => 'tax',      'calculation_type' => 'subtract', 'is_tax' => true],
        ];

        foreach ($components as $data) {
            CompanySalary::create($data);
        }

        $employees = User::where('type', 'employee')->get();
        $companyUser = User::where('email', 'company@admin.com')->first();

        foreach ($employees as $employee) {
            $this->assignSalary($employee, 'Gaji Pokok', true, false);
            $this->assignSalary($employee, 'Tunjangan Transportasi', true, false);
            $this->assignSalary($employee, 'BPJS Kesehatan', true, false);
            $this->assignSalary($employee, 'PPh 21', true, false);
        }

        $this->assignSalary(User::where('email', 'rudi@email.com')->first(),   'Tunjangan Makan', true, false);
        $this->assignSalary(User::where('email', 'agus@email.com')->first(),   'Tunjangan Makan', true, false);
        $this->assignSalary(User::where('email', 'dewi@email.com')->first(),   'Tunjangan Makan', true, false);
        $this->assignSalary(User::where('email', 'indah@email.com')->first(),  'Tunjangan Makan', true, false);
        $this->assignSalary(User::where('email', 'fitri@email.com')->first(),  'Tunjangan Makan', true, false);
        $this->assignSalary(User::where('email', 'agus@email.com')->first(),   'Tunjangan Transportasi', false, true);
        $this->assignSalary(User::where('email', 'rina@email.com')->first(),   'Tunjangan Transportasi', false, true);
        $this->assignSalary(User::where('email', 'andi@email.com')->first(),   'Tunjangan Transportasi', false, true);

        $createRequest = function ($email, $componentName, $quantity, $status) use ($companyUser) {
            $user = User::where('email', $email)->first();
            $salary = CompanySalary::where('name', $componentName)->first();

            if ($user && $salary) {
                EmployeeRequestedSalary::create([
                    'mst_user_id' => $user->id,
                    'mst_company_salary_id' => $salary->id,
                    'quantity' => $quantity,
                    'quantity_snapshot' => $quantity,
                    'salary_snapshot' => $salary->salary,
                    'status' => $status,
                    'mst_approved_by' => $status === 'approved' ? $companyUser->id : null,
                    'approved_date' => $status === 'approved' ? now()->subDays(5) : null,
                    'is_realized' => false,
                ]);
            }
        };

        $createRequest('agus@email.com', 'Tunjangan Transportasi', 20, 'approved');
        $createRequest('rina@email.com', 'Tunjangan Transportasi', 5, 'approved');
        $createRequest('andi@email.com', 'Tunjangan Transportasi', 10, 'pending');
    }

    private function assignSalary(User $user, string $componentName, bool $default, bool $requestable): void
    {
        $salary = CompanySalary::where('name', $componentName)->first();
        if (!$salary || !$user) return;

        DB::table('pivot_employee_salary')->updateOrInsert(
            [
                'mst_company_salary_id' => $salary->id,
                'mst_user_id' => $user->id,
            ],
            [
                'included_at_default' => $default,
                'available_to_request' => $requestable,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
