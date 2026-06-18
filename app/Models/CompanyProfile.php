<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $table = 'mst_company_profile';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'auto_approve' => 'boolean',
            'auto_approve_duplicate_coords' => 'boolean',
        ];
    }
}
