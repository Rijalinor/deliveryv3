<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    protected $fillable = [
        'driver_id',
        'start_date',
        'start_time',
        'start_lat',
        'start_lng',
        'status',
        'generated_at',
        'total_distance_m',
        'total_duration_s',
        'route_geojson',
        'ors_profile',
    ];

    protected $casts = [
        'start_date' => 'date',
        'generated_at' => 'datetime',
        'start_lat' => 'float',
        'start_lng' => 'float',
    ];

    
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TripStop::class);
        
    }
}
