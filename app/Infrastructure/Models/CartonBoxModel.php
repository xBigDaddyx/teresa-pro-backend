<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CartonBoxModel extends Model
{
    use HasUuids, LogsActivity;

    protected $table = 'carton_boxes';

    protected $fillable = [
        'packing_list_id',
        'internal_sku',
        'barcode',
        'details',
        'validation_status',
        'status',
        'items_quantity',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'details' => 'array',
        'status' => \App\Enums\CartonStatus::class,
        'validation_status' => \App\Enums\CartonValidationStatus::class,
    ];

    public function packingList()
    {
        return $this->belongsTo(PackingListModel::class, 'packing_list_id');
    }

    public function items()
    {
        return $this->belongsToMany(ItemModel::class, 'carton_box_items')
            ->withPivot('is_validated', 'validated_at', 'validated_by')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['barcode', 'internal_sku', 'validation_status', 'status', 'items_quantity'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cartonBox) {
            if ($cartonBox->packingList) {
                $cartonBox->internal_sku = \App\Services\SkuService::generateSku($cartonBox->packingList, 'CARTON');
            }
        });
    }
}
