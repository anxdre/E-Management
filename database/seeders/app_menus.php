<?php

namespace Database\Seeders;

use App\Models\AppMenu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class app_menus extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu =[
            'presence_location',
            'employee_presence',
            'employee_group',
            'employee_account',
            'company_salary',
            'employee_payroll',
            'employee_devices'
        ];
    }
}
