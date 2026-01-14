<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripStop extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'trip_id',
        'store_id',
        'status',
        'sequence',
        'eta_at' => 'datetime',
        'close_at' => 'datetime',
        'is_late',
        'late_minutes',
        'skip_reason',
        'arrived_at',
        'done_at',
        'skipped_at',
    ];
    public function trip()
    {
        return $this->belongsTo(\App\Models\Trip::class);
    }
    public function store()
    {
        return $this->belongsTo(\App\Models\Store::class);
    }
}
