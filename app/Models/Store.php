<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'address', 'lat', 'lng', 'close_time',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'close_time' => 'string', // TIME dari DB sebagai string "HH:MM:SS"
    ];
}
