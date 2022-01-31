<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class PackagingCenter extends Authenticatable
{
    protected $guarded = [];

    protected $fillable = [
        'center','center_phone', 'center_address', 'created_at', 'updated_at'
    ];

}
