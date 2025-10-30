<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\Employee\EmployeeAccountController;
use App\Http\Controllers\Employee\EmployeeGroupController;
use App\Http\Controllers\MobileApi\AuthApiController;
use App\Http\Controllers\MobileApi\LocationApiController;
use App\Http\Controllers\MobileApi\PrensenceApiController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\Payroll\CompanySalaryController;
use App\Http\Controllers\Payroll\CompanyPayrollReceiptController;
use App\Http\Controllers\Payroll\EmployeePayrollTypeController;
use App\Http\Controllers\Presence\EmployeePresenceController;
use App\Http\Controllers\Presence\PresenceLocationController;
use App\Http\Controllers\Presence\PresenceVerificationController;

return [

    /*
    |--------------------------------------------------------------------------
    | Preloads
    |--------------------------------------------------------------------------
    | String of class name that instance of \Dentro\Yalr\Contracts\Bindable
    | Preloads will always been called even when laravel routes has been cached.
    | It is the best place to put Rate Limiter and route binding related code.
    */

    'preloads' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Router group settings
    |--------------------------------------------------------------------------
    | Groups are used to organize and group your routes. Basically the same
    | group that used in common laravel route.
    |
    | 'group_name' => [
    |     // laravel group route options can contains 'middleware', 'prefix',
    |     // 'as', 'domain', 'namespace', 'where'
    | ]
    */

    'groups' => [
        'web' => [
            'middleware' => 'web',
            'prefix' => '',
        ],
        'api' => [
            'middleware' => 'api',
            'prefix' => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    | Below is where our route is loaded, it read `groups` section above.
    | keys in this array are the name of route group and values are string
    | class name either instance of \Dentro\Yalr\Contracts\Bindable or
    | controller that use attribute that inherit \Dentro\Yalr\RouteAttribute
    */

    'web' => [
        /** @inject web **/
//        authentication
        AuthController::class,
        EmailController::class,
        PasswordController::class,
        DashboardController::class,

        //employee
        EmployeeAccountController::class,
        EmployeeGroupController::class,

        //presence
        PresenceLocationController::class,
        PresenceVerificationController::class,
        EmployeePresenceController::class,

        //payroll
        CompanySalaryController::class,
        EmployeePayrollTypeController::class,

        //receipt
        CompanyPayrollReceiptController::class

    ],
    'api' => [
        /** @inject api **/
        AuthApiController::class,
        PrensenceApiController::class,
        LocationApiController::class,
    ],
];
