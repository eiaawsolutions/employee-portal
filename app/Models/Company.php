<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'address', 'registration_number', 'phone', 'kwsp_number', 'tin_number', 'socso_number', 'logo_path'];
}