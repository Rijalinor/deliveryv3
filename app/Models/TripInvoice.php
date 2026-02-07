<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'trip_stop_id',
        'pfi_number',
        'amount',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(TripStop::class, 'trip_stop_id');
    }
}
