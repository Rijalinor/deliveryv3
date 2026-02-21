<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'address', 'lat', 'lng', 'open_time', 'close_time', 'service_minutes',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'open_time' => 'string',
        'close_time' => 'string',
        'service_minutes' => 'integer',
    ];
}
