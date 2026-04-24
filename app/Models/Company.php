<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'address', 'registration_number', 'phone', 'kwsp_number', 'tin_number', 'socso_number', 'eis_number', 'logo_path'];
}