<?php

namespace App\Models\UserManagement;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\CompanyProfile;
use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\EmployeeSalary;
use App\Models\PresenceManagement\PresenceEmployee;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $table = 'mst_users';

    protected $guarded = [
        'id','remember_token',
    ];

    protected $primaryKey = 'id';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    function userDetail()
    {
        return $this->belongsTo(UserDetail::class, 'mst_user_detail_id')->withTrashed()->withDefault();
    }

    function companyProfile()
    {
        return $this->hasOne(CompanyProfile::class, 'mst_user_id');
    }

    function groups()
    {
        return $this->belongsToMany(CompanyGroup::class, 'pivot_group_has_users', 'mst_user_id', 'mst_company_group_id');
    }

    function presence(){
        return $this->hasMany(PresenceEmployee::class, 'mst_user_id', 'id');
    }

    public function isCompany(): bool
    {
        return $this->type == 'company';
    }

    public function isEmployee(): bool
    {
        return $this->type == 'employee';
    }

    public function isSuper(): bool
    {
        return $this->type == 'superadmin';
    }

    public function salaries()
    {
        return $this->belongsToMany(CompanySalary::class, 'pivot_employee_salary', 'mst_user_id', 'mst_company_salary_id')
            ->using(EmployeeSalary::class)
            ->withPivot(['available_to_request', 'included_at_default'])
            ->withTimestamps();
    }

    public function defaultSalaries()
    {
        return $this->salaries()->wherePivot('included_at_default', true);
    }

    public function requestableSalaries()
    {
        return $this->salaries()->wherePivot('available_to_request', true);
    }
}
