<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PackingListModel extends Model
{
    use HasUuids, LogsActivity;

    protected $table = 'packing_lists';

    protected $fillable = [
        'buyer_id',
        'purchase_order_number',
        'carton_boxes_quantity',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function buyer()
    {
        return $this->belongsTo(BuyerModel::class, 'buyer_id');
    }

    public function cartonBoxes()
    {
        return $this->hasMany(CartonBoxModel::class, 'packing_list_id');
    }

    public function scopeFullyFilled($query)
    {
        return $query->whereHas('cartonBoxes')
            ->whereRaw('(SELECT COUNT(*) FROM carton_boxes WHERE carton_boxes.packing_list_id = packing_lists.id) >= carton_boxes_quantity');
    }

    public function scopeUnfilled($query)
    {
        return $query->whereHas('cartonBoxes')
            ->whereRaw('(SELECT COUNT(*) FROM carton_boxes WHERE carton_boxes.packing_list_id = packing_lists.id) < carton_boxes_quantity')
            ->orWhereDoesntHave('cartonBoxes');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['purchase_order_number', 'carton_boxes_quantity', 'details', 'buyer_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
