<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Logistic extends Authenticatable
{
    protected $guarded = [];

    // protected $fillable = [
    //     'company', 'email', 'phone', 'photo', 'address', 'password', 'status', 'remember_token', 'created_at', 'updated_at', 'date'
    // ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function withdraws()
    {
        return $this->hasMany('App\Models\Withdraw');
    }

}
