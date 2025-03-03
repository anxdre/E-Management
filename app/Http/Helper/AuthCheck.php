<?php

namespace App\Http\Helper;

use App\Models\UserManagement\User;
use Illuminate\Support\Facades\Auth;

trait AuthCheck
{
    function isEmployeeData($id)
    {
        $user = User::query()->select('id','type','company_id')->where('id', $id)->first();
        $granted = false;

        if (Auth::id() == $user->id) {
            $granted = true;
        }

        return $granted;
    }

    function isEmployeeOfCompany($companyId)
    {
        $company = User::query()->select('id','type','company_id')->where('id', $companyId)->first();
        $granted = false;

        if (Auth::user()->company_id == $company->id) {
            $granted = true;
        }

        return $granted;
    }

    function isAdminOfCompany($id)
    {
        $user = User::query()->select('id','type','company_id')->where('id', $id)->first();
        $granted = false;

        if (Auth::id() == $user->company_id) {
            $granted = true;
        }

        return $granted;
    }
}
