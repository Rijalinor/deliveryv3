<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripStop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_id',
        'store_id',
        'status',
        'sequence',
        'eta_at',
        'close_at',
        'is_late',
        'late_minutes',
        'skip_reason',
        'arrived_at',
        'done_at',
        'skipped_at',
    ];

    protected $casts = [
        'eta_at' => 'datetime',
        'close_at' => 'datetime',
        'arrived_at' => 'datetime',
        'done_at' => 'datetime',
        'skipped_at' => 'datetime',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'sequence' => 'integer',
    ];

    public function trip()
    {
        return $this->belongsTo(\App\Models\Trip::class);
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }

    public function invoices()
    {
        return $this->hasMany(\App\Models\TripInvoice::class);
    }

    public function arrivedToFinishMinutes(): ?int
    {
        if (! $this->arrived_at) {
            return null;
        }

        $end = $this->done_at ?? $this->skipped_at;
        if (! $end) {
            return null;
        }

        return $this->arrived_at->diffInMinutes($end);
    }
}
