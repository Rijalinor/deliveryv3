<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsIssueItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_issue_id',
        'pfi_number',
        'store_name',
        'address',
        'amount',
        'store_id',
    ];

    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
