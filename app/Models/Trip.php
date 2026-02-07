<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    protected $fillable = [
        'driver_id',
        'gi_number',
        'start_date',
        'start_time',
        'start_address',
        'start_lat',
        'start_lng',
        'status',
        'notice',
        'generated_at',
        'total_distance_m',
        'total_duration_s',
        'route_geojson',
        'ors_profile',
        'service_minutes',
        'traffic_factor',
    ];

    protected $casts = [
        'start_date' => 'date',
        'generated_at' => 'datetime',
        'start_lat' => 'float',
        'start_lng' => 'float',
        'service_minutes' => 'integer',
        'traffic_factor' => 'float',
    ];

    
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TripStop::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(TripInvoice::class, TripStop::class);
    }
}
