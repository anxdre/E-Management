<?php

namespace App\Models\UserManagement;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Payroll\CompanySalary;
use App\Models\Payroll\EmployeeSalary;
use App\Models\PresenceManagement\PresenceEmployee;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        return $this->hasOne(UserDetail::class, 'id','user_detail_id');
    }

    function groups()
    {
        return $this->belongsToMany(CompanyGroup::class, 'group_has_users', 'user_id', 'group_id');
    }

    function presence(){
        return $this->hasMany(PresenceEmployee::class, 'user_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function employees()
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function isCompany(): bool
    {
        return is_null($this->company_id);
    }

    public function isEmployee(): bool
    {
        return !is_null($this->company_id);
    }

    public function salaries()
    {
        return $this->belongsToMany(CompanySalary::class, 'employee_salary', 'user_id', 'company_salary_id')
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
