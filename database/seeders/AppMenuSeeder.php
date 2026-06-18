<?php

namespace Database\Seeders;

use App\Models\AppMenu;
use Illuminate\Database\Seeder;

class AppMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'presence_location',
            'employee_presence',
            'employee_group',
            'employee_account',
            'company_salary',
            'employee_payroll',
            'employee_devices',
        ];

        foreach ($menus as $menu) {
            AppMenu::firstOrCreate(['name' => $menu]);
        }
    }
}
