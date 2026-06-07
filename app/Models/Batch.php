<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Batch extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'code',
        'asset_type',
        'notes',
        'created_by',
        'printed_at',
        'total_assets',
        'generated',
        'remaining',
        'validity'
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];   

    public function assets(): HasMany
    {
        return $this->hasMany(SafeAsset::class, 'batch_id');
    }
}