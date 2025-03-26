<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ItemModel extends Model
{
    use HasUuids;

    protected $table = 'items';

    protected $fillable = [
        'internal_sku',
        'name',
        'barcode',
        'details',
        'has_polybag',
    ];

    protected $casts = [
        'details' => 'array',
        'has_polybag' => 'boolean',
    ];

    public function cartonBoxes()
    {
        return $this->belongsToMany(CartonBoxModel::class, 'carton_box_items')
            ->withPivot('is_validated', 'validated_at', 'validated_by')
            ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($item) {
            $item->internal_sku = \App\Services\SkuService::generateSku(null, 'ITEM', $item);
        });
    }
}
