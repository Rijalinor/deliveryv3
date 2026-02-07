<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'gi_number',
        'date',
        'status',
        'trip_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
