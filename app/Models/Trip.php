<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

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

    /**
     * Estimasi Biaya BBM (Manusiawi)
     */
    public function getEstimatedFuelCostAttribute(): float
    {
        $distanceKm = ($this->total_distance_m ?? 0) / 1000;
        $kmPerLiter = (float) config('delivery.fuel_km_per_liter', 10);
        $pricePerLiter = (float) config('delivery.fuel_price_per_liter', 13000);
        $safetyFactor = (float) config('delivery.fuel_safety_factor', 1.20);

        if ($kmPerLiter <= 0) {
            return 0;
        }

        return ($distanceKm / $kmPerLiter) * $pricePerLiter * $safetyFactor;
    }
}
