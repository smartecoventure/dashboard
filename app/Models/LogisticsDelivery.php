<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class LogisticsDelivery extends Authenticatable
{
    protected $guarded = [];

    protected $fillable = [
        'logistics_id','order_number','time_pickup'
    ];

}
